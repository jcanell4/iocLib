<?php
/**
 * Description of FtpSender
 */
class FtpSender{
    private $ftpObjectToSendList;

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
            $directory = substr($file   , 0, -4);
        }

        $destination .= "$directory/";

        if ($ret) {
            $ret = $this->_iocUnzipAndFtpSend($tmp_dir, $destination);
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
                        $ret = $ret && $this->remoteSSH2Copy($file, $source, $file, $destination);
                    }
                }
            }
        }
        return $ret;
    }
    
//    private function ssh2Mkdir($conection, $dir, $mode=0777){
//        $cmd = "mkdir -R /$dir";
//        $ret = ssh2_exec($conection, $cmd);
//        return $ret;
//    }
    
//    private function ssh2CopyFile($sftp, $srcFile, $dstFile){
//        $ret = true;
//        $sftpStream = @fopen('ssh2.sftp://'.$sftp.$dstFile, 'w');
//
//        try {
//
//            if (!$sftpStream) {
//                throw new Exception("Could not open remote file: $dstFile");
//            }
//
//            $data_to_send = @file_get_contents($srcFile);
//
//            if ($data_to_send === false) {
//                throw new Exception("Could not open local file: $srcFile.");
//            }
//
//            if (@fwrite($sftpStream, $data_to_send) === false) {
//                throw new Exception("Could not send data from file: $srcFile.");
//            }
//
//            fclose($sftpStream);
//
//        } catch (Exception $e) {
//            error_log('Exception: ' . $e->getMessage());
//            $ret = false;
//            fclose($sftpStream);
//        }       
//        return $ret;
//    }

    private function remoteSSH2Copy($file, $local, $remoteFile, $remote) {
        $ret = FALSE;
        $host = $this->connectionData['sendftp_host'];
        $port = $this->connectionData['sendftp_port'];
        $user = $this->connectionData['sendftp_u'];
        $pass = $this->connectionData['sendftp_p'];


        $connection = ssh2_connect($host, $port);
        if ($connection) {
            if (($ret = ssh2_auth_password($connection, $user, $pass))) {
                $ret = $sftp = ssh2_sftp($connection);
                if ($sftp) {
                    if($remote[0]==='/'){
                        $remote = $remote[-strlen($remote)+1];
                    }
                    if($remote[strlen($remote)-1]!=='/'){
                        $remote .= '/';
                    }
                    $ret = ssh2_sftp_mkdir($sftp, $remote, 0777, TRUE);
//                    $ret = $this->ssh2Mkdir($connection, $remote);
                    if($ret){
                        Logger::debug("S'ha creat el directori '$remote'", 1, 114, "FtpSender.php", 1);
                    }
                    $ret = ssh2_scp_send($connection, "$local$file", "$remote$remoteFile");
//                    $ret = $this->ssh2CopyFile($sftp, "$local$file", "/$remote$remoteFile");
                }
                if($ret){
                    Logger::debug("Enviament EXITOS de $local$file a $remote$remoteFile", 0, 118, "FtpSender.php", 1);
                }else{
                    Logger::debug("Enviament FALLIT de $local$file a $remote$remoteFile", 1, 118, "FtpSender.php", 1);
                }
            }else{
                Logger::debug("Connexió fallida a $host:$port(u:$user, p:$pass)", 1, 105, "FtpSender.php", 1);
            }
            $this->ssh2_disconnect($connection);
        }else{
            Logger::debug("Connexió fallida a $host:$port(u:$user, p:$pass)", 1, 103, "FtpSender.php", 1);
        }
        return $ret;
    }

    private function ssh2_disconnect($connection) {
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
