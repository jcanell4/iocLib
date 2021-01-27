<?php
if (!defined('DOKU_INC')) die();

class WorkflowProjectMetaDataAction extends ProjectMetadataAction {

    public function getActionInstance($param){
        $action = parent::getActionInstance($this->getActionName($param));
        return $action;
    }

    public function responseProcess() {
        return parent::responseProcess();
    }

    private function getActionName($action) {
        $actions = ["edit" => "GetProjectMetaDataAction"
                   ];
        return $actions[$action];
    }

}