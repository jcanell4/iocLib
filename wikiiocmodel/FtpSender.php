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

    public function addObjectToSendList($file, $local, $remoteBase, $remoteDir, $action=0, $remoteFile=""){

        if ($this->connectionData == NULL) {
            throw new Exception("S'ha de passar la informació de conexió mitjançant setConnectionData abans d'afegir objectes a la llista");
        }

        $this->ftpObjectToSendList[] = new FtpObjectToSend($file, $local, $remoteBase, $remoteDir, $action, $remoteFile, $this->connectionData);
    }

    public function process() {
        //Codificar l'enviament de cada fitxer de la llista d'acord amb els seus paràmetres
        //tractar les respostes a la variable $response per tal de poder informar del que
        //ha passat duarnt la connexió
        Logger::debug("FtpSender::process", 0, 27, "FtpSender.php", 1, FALSE);
        foreach ($this->ftpObjectToSendList as $oFtp) {
            $action = $oFtp->getAction();
            foreach ($action as $act) {
                switch ($act) {
                    case FtpObjectToSend::COPY_ACTION:
                        $response = $this->remoteSSH2Copy($oFtp->getFile(), $oFtp->getLocal(), $oFtp->getRemoteFile(), $this->connectionData['remoteBase'].$this->connectionData['remoteDir']);
                        break;

                    case FtpObjectToSend::UNZIP_AND_COPY_ACTION:
                        $response = $this->iocUnzipAndFtpSend($oFtp->getFile(), $oFtp->getLocal(), $this->connectionData['remoteBase'].$this->connectionData['remoteDir']);
                        break;
                }
            }
        }

        return $response;
    }

    private function remoteSSH2Copy($local_file, $source, $remote_file, $remote_dir) {
        if (($ret = $this->connectSSH2())) {
            $ret = uploadFile($local_file, $source, $remote_file, $remote_dir);
            $this->ssh2_disconnect($this->connection);
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
        $remote_dir = "/".trim($remote_dir,"/")."/";
        ssh2_sftp_mkdir($this->sftp, $remote_dir, 0777, TRUE);

        $stream = @fopen("ssh2.sftp://{$this->sftp}$remote_dir$remote_file", 'w');
        if (! $stream)
            throw new Exception("Could not open file: $remote_dir$remote_file");

        $data_to_send = @file_get_contents("$source$local_file");
        if ($data_to_send === false)
            throw new Exception("Could not open local file: $source$local_file.");

        if (@fwrite($stream, $data_to_send) === false)
            throw new Exception("Could not send data from file: $source$local_file.");

        @fclose($stream);
        return TRUE;
    }

    private function connectSSH2() {
        $ret = FALSE;
        $host = $this->connectionData['sendftp_host'];
        $port = $this->connectionData['sendftp_port'];
        $user = $this->connectionData['sendftp_u'];
        $pass = $this->connectionData['sendftp_p'];

        $this->connection = ssh2_connect($host, $port);
        if ($this->connection) {
            if (($ret = ssh2_auth_password($this->connection, $user, $pass))) {
                $this->sftp = $ret = ssh2_sftp($this->connection);
            }
        }
        return $ret;
    }

    private function ssh2_disconnect(&$connection) {
        if (PHP_VERSION_ID < 70000) {
            unset($connection);
        }else{
            ssh2_disconnect($connection);
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
