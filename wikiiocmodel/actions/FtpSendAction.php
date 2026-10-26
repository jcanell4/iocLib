<?php
/**
 * Description of FtpSendAction
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC."lib/lib_ioc/");
require_once DOKU_LIB_IOC . "wikiiocmodel/FtpSender.php";

class FtpSendAction extends DokuAction{
    private $dokuPageModel;
    private $ftpSender;
    private $ftpResponse;

    public function __construct($params = NULL) {
        parent::__construct($params);
        $this->ftpSender = new FtpSender();
    }

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->dokuPageModel = new DokuPageModel($modelManager->getPersistenceEngine());
    }

    protected function getModel() {
        return $this->dokuPageModel;
    }

    protected function startProcess() {
        //afegir a la llista d'objectes a enviar quins fitxers i sota quins paràmetres caldrà fer-ho
        $remoteFilename ="web";
        $filename = str_replace(':', '_', $this->params[ProjectKeys::KEY_ID]).".zip";
        $dest = dirname(str_replace(':', '/', $this->params[ProjectKeys::KEY_ID]))."/";
        $remoteDest = str_replace('/', '_', $dest);
        $local = WikiGlobalConfig::getConf('mediadir')."/".$dest;

        $ftpConfigs = WikiGlobalConfig::getConf(AjaxKeys::KEY_FTP_CONFIG, 'iocexportl');
        $connectionData = $ftpConfigs['materials_fp'];
        $this->ftpSender->setConnectionData($connectionData);

        $this->ftpSender->addObjectToSendList($filename, $local, $connectionData["remoteBase"].$connectionData["remoteDir"], "$remoteDest/", [0], "$remoteFilename.zip");
        $this->ftpSender->addObjectToSendList($filename, $local, $connectionData["remoteBase"].$connectionData["remoteDir"], "$remoteDest/$remoteFilename/", [1]);
    }

    protected function responseProcess() {
        $id = $this->params[ProjectKeys::KEY_ID];
        if ($this->ftpResponse) {
            $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('ftp_send_success')." ($id)", $id);
        }else {
            $response['info'] = self::generateInfo("error", WikiIocLangManager::getLang('ftp_send_error')." ($id)", $id);
            $response['alert'] = WikiIocLangManager::getLang('ftp_send_error')." ($id)";
        }
        return $response;
    }

    protected function runProcess() {
        $this->ftpResponse = $this->ftpSender->process();
    }
}
