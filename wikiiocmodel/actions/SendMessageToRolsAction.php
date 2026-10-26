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
        $id = $this->params['id'];
        $rols = explode(",", trim($this->params['rols'], ","));
        $checked_items = json_decode($this->params['checked_items'], true);
        foreach ($checked_items as $ns) {
            $users = $this->model->getUserRol($rols, $ns);
            if (!empty($users)) {
                $this->params['id'] = $ns;
                $this->params['to'] = implode(",", $users);
                $workflow = $this->model->isProjectTypeWorkflow($this->model->getProjectType($ns));
                $this->params["data-call"] = ($workflow) ? "project&do=workflow&action=view" : "project&do=view";
                $response['notifications'] = [];
                $notifyResponse = $this->notifyMessageToFrom();
                $response['notifications'][] = $notifyResponse['notifications'];
                $response['info'] = $notifyResponse['info'];
            }
        }
        if (empty($this->params['to'])) {
            $response['info'] = self::generateInfo("warning", "No hi ha cap destinatari", $this->params[ProjectKeys::KEY_ID], 15);
        }
        $this->params['id'] = $id;
        return $response;
    }

}

