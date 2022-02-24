<?php
/**
 * SendMessageToRolsAction: Envia una notificació i un missatge als destinataris definits pel seu rol als projectes seleccionats
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class SendMessageToRolsAction extends NotifyAction {

    private $persistenceEngine;
    private $model;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->persistenceEngine = $modelManager->getPersistenceEngine();
        $ownModel = "AdminModel";
        $this->model = new $ownModel($this->persistenceEngine);
    }

    protected function responseProcess() {
        $rols = explode(",", trim($this->params['rols'], ","));
        $this->params['message'] .= ".\\\\ Llista de projectes: ";
        $checked_items = json_decode($this->params['checked_items'], true);
        foreach ($checked_items as $ns) {
            $this->params['message'] .= "\\\\ - [[".DOKU_URL."doku.php?id=$ns|$ns]]";
            $users .= $this->model->getUserRol($rols, $ns) . ",";
        }
        $users = explode(",",  trim($users, ","));
        $users = array_unique($users);
        $this->params['to'] = implode(",", $users);
        if (!empty($this->params['to'])) {
            $response['notifications'] = [];
            $notifyResponse = $this->notifyMessageToFrom();
            $response['notifications'][] = $notifyResponse['notifications'];
            $response['info'] = $notifyResponse['info'];
        }else {
            $response['info'] = self::generateInfo("warning", "No hi ha cap destinatari", $this->params[ProjectKeys::KEY_ID], 15);
        }
        return $response;
    }

}

