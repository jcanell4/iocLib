<?php
/**
 * Description of FtpSender
 */
class FtpSender{
    private $ftpObjectToSendList;
    private $connection;
    private $sftp;

    protected $connectionData;

    public function __construct() {
        $this->ftpObjectToSendList = array();
    }

    public function addObjectToSendList($file, $local, $remoteBase="", $remoteDir="", $action=0, $remoteFile=""){

        if ($this->connectionData == NULL) {
            throw new Exception("S'ha de passar la informació de conexió mitjançant setConnectionData abans d'afegir objectes a la llista");
        }

        $this->ftpObjectToSendList[] = new FtpObjectToSend($file, $local, $remoteBase, $remoteDir, $action, $remoteFile, $this->connectionData);
    }

    public function process() {
        //Codificar l'enviament de cada fitxer de la llista d'acord amb els seus paràmetres
        //tractar les respostes a la variable $response per tal de poder informar del que ha passat duarnt la connexió
        //Logger::debug("FtpSender::process-INI", 0, __LINE__, "FtpSender.php", 1);
        $response=TRUE;
        foreach ($this->ftpObjectToSendList as $oFtp) {
            $action = $oFtp->getAction();
            if(empty($oFtp->getRemoteBase())){
                $remoteBase = $this->connectionData['remoteBase'];
            }else{
                $remoteBase = $oFtp->getRemoteBase();
            }
            if(empty($oFtp->getRemoteDir())){
                $remoteDir = $this->connectionData['remoteDir'];
            }else{
                $remoteDir = $oFtp->getRemoteDir();
            }
            foreach ($action as $act) {
                switch ($act) {
                    case FtpObjectToSend::COPY_ACTION:
                        $response = $response && $this->remoteSSH2Copy($oFtp->getFile(), $oFtp->getLocal(), $oFtp->getRemoteFile(), $remoteBase.$remoteDir);
                        break;

                    case FtpObjectToSend::UNZIP_AND_COPY_ACTION:
                        $response = $response && $this->iocUnzipAndFtpSend($oFtp->getFile(), $oFtp->getLocal(), $remoteBase.$remoteDir);
                        break;
                }
            }
        }

        //Logger::debug("FtpSender::process-END: response: $response", 0, __LINE__, "FtpSender.php", 1);
        return $response;
    }

    private function remoteSSH2Copy($local_file, $source, $remote_file, $remote_dir) {
        if (($ret = $this->connectSSH2())) {
            //Logger::debug("FtpSender::remoteSSH2Copy-uploading file", 0, __LINE__, "FtpSender.php", 1);
            $ret = $this->uploadFile($local_file, $source, $remote_file, $remote_dir);
            $this->ssh2_disconnect($this->connection);
            //Logger::debug("FtpSender::remoteSSH2Copy-ssh2_disconnect", 0, __LINE__, "FtpSender.php", 1);
            return $ret;
        }
    }

    /**
     * @param string $file nom del fitxer zip
     * @param string $source ruta d'origen
     * @param string $destination ruta de destí
     * @param string $directory opcional, si
     * @return bool
     */
    public function iocUnzipAndFtpSend($file, $source, $destination, $directory = '') {
        if (!defined('EXPORT_TMP')) define('EXPORT_TMP', DOKU_PLUGIN."tmp/latex/");
        $tmp_dir = realpath(EXPORT_TMP)."/".rand()."/";
        if (!file_exists($tmp_dir)) mkdir($tmp_dir, 0775, TRUE);
        $zip = new ZipArchive;
        if ($zip->open($source.$file) === TRUE) {
            $zip->extractTo($tmp_dir);
            $zip->close();
            $ret = TRUE;
        }

        if ($directory === '') {
            $directory = substr($file, 0, -4);
        }
        $destination .= "$directory/";

        if ($ret) {
            if (($ret = $this->connectSSH2())) {
                $ret = $this->_iocUnzipAndFtpSend($tmp_dir, $destination);
                $this->ssh2_disconnect($this->connection);
            }
        }
        if ($ret) {
            IocCommon::removeDir($tmp_dir);
        }
        return $ret;
    }

    private function _iocUnzipAndFtpSend($source, $destination) {
        //Logger::debug("FtpSender::_iocUnzipAndFtpSend-strating", 0, __LINE__, basename(__FILE__), 1);
        if (($dir = @opendir($source))) {
            $ret = TRUE;
            while ($file = readdir($dir)) {
                if ($file!=="." && $file!=="..") {
                    if (is_dir("$source$file")) {
                        $ret = $ret && $this->_iocUnzipAndFtpSend("$source$file/", "$destination$file/");
                    }else{
                        $ret = $ret && $this->uploadFile($file, $source, $file, $destination);
                    }
                }
            }
        }
        return $ret;
    }

    private function uploadFile($local_file, $source, $remote_file, $remote_dir) {
        //Logger::debug("FtpSender::uploadFile-start: $local_file", 0, __LINE__, basename(__FILE__), 1);
        if (($fh = fopen("$source$local_file", "rb"))) {
            $buffer = fread($fh, 4096);
            fclose($fh);
        }
        if (empty($buffer))
            throw new Exception("Could not open local file: $source$local_file.");

        $fh = fopen("$source$local_file", "rb");
        if ($fh) {
            $maxBytes = WikiGlobalConfig::getConf('maxVarLengthFTP', 'wikiiocmodel');
            $remote_dir = "/".trim($remote_dir,"/")."/";
            ssh2_sftp_mkdir($this->sftp, $remote_dir, 0777, TRUE);

            $stream = @fopen("ssh2.sftp://{$this->sftp}$remote_dir$remote_file", 'w');
            if (! $stream)
                throw new Exception("Could not open remote file: $remote_dir$remote_file");

            while (! feof($fh)) {
                $data_to_send = @fread($fh, $maxBytes); //Llegir 1 MB
                if (@fwrite($stream, $data_to_send) === false)
                    throw new Exception("Could not send data from file: $source$local_file.");
            }
            $ret = fclose($stream);
            fclose($fh);
        }
        //Logger::debug("FtpSender::uploadFile-end: \$ret=$ret", 0, __LINE__, basename(__FILE__), 1);
        return $ret;
    }

    private function connectSSH2() {
        $ret = FALSE;
        $host = $this->connectionData['sendftp_host'];
        $port = $this->connectionData['sendftp_port'];
        $user = $this->connectionData['sendftp_u'];
        $pass = $this->connectionData['sendftp_p'];

        //$methods = ['hostkey' => 'ssh-rsa'];
        //$callbacks = ['disconnect' => [$this, 'callback_ssh_disconnect']];
        //$this->connection = ssh2_connect($host, $port, $methods, $callbacks);

        $this->connection = ssh2_connect($host, $port);
        if ($this->connection) {
            if (($ret = ssh2_auth_password($this->connection, $user, $pass))) {
                $this->sftp = $ret = ssh2_sftp($this->connection);
            }
        }
        return $ret;
    }

    // Notifica al usuario si el servidor ha terminado la conexión
    public function callback_ssh_disconnect($reason, $message, $language) {
        $message = sprintf("Servidor desconectado con el código [%d] y mensaje: %s\n", $reason, $message);
        Logger::debug("FtpSender::my_ssh_disconnect: $message", 0, __LINE__, "FtpSender.php", 1);
    }

    private function ssh2_disconnect(&$connection) {
        if (PHP_VERSION_ID < 70000) {
            unset($connection);
        }else{
            //Verificar cual de los dos procedimientos produce realmente la desconexxión en PHP 7.x
            unset($connection);
            //ssh2_disconnect($connection);
        }
    }

    public function setConnectionData($connectionData) {
        $this->connectionData = $connectionData;
    }
}

class FtpObjectToSend {
    const COPY_ACTION = 0;
    const UNZIP_AND_COPY_ACTION = 1;

    private $remoteFile;
    private $file;
    private $local;
    private $remoteBase;
    private $remoteDir;
    private $action;
    private $connectionData;

    public function __construct($file, $local, $remoteBase, $remoteDir, $action, $remoteFile="", $connectionData=NULL) {
        $this->file = $file;
        $this->local = $local;
        $this->remoteBase= $remoteBase;
        $this->remoteDir= $remoteDir;
        $this->action= $action;
        $this->remoteFile= empty($remoteFile)?$file:$remoteFile;
        $this->connectionData = $connectionData;
    }

    public function getFile(){
        return $this->file;
    }

    public function getRemoteFile(){
        return $this->remoteFile;
    }

    public function getLocal(){
        return $this->local;
    }

    public function getRemoteBase(){
        return $this->remoteBase;
    }

    public function getRemoteDir(){
        return $this->remoteDir;
    }

    public function getAction(){
        return $this->action;
    }
}
