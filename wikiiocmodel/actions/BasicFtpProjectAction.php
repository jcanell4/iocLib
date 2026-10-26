<?php
/**
 * Description of BasicFtpProjectAction
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC . "lib/lib_ioc/");
require_once DOKU_LIB_IOC . "wikiiocmodel/FtpSender.php";

class BasicFtpProjectAction extends ProjectAction{

    protected $ftpSender;

    public function __construct($params = NULL) {
        parent::__construct($params);
        $this->ftpSender = new FtpSender();
    }

    private function addFilesToSend() {
        // $filesToSend es un array de n arrays con el formato ['file', 'local', 'action', 'remoteBase', 'remoteDir']
        $filesToSend = $this->getModel()->filesToExportList(true); //crear la funció filesToExportList a cada projectModel amb les dades a tractar

        if ($filesToSend) {
            foreach ($filesToSend as $afile) {
                $connectionData = $this->getModel()->getFtpConfigData($afile[ProjectKeys::KEY_FTPID]);  //datos de conexión de local.protected
                $this->ftpSender->setConnectionData($connectionData);
                $this->ftpSender->addObjectToSendList($afile['file'], $afile['local'], $afile['remoteBase'], $afile['remoteDir'], $afile['action']);
            }
        }
    }

    protected function responseProcess() {
        $this->addFilesToSend();
        $ftpResponse = $this->ftpSender->process();

        $id = $this->params[ProjectKeys::KEY_ID];
        if ($ftpResponse) {
            $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('ftp_send_success')." ($id)", $id);
        }else {
            $response['info'] = self::generateInfo("error", WikiIocLangManager::getLang('ftp_send_error')." ($id)", $id);
            $response['alert'] = WikiIocLangManager::getLang('ftp_send_error')." ($id)";
        }

        return $response;
    }
}
