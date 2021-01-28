<?php
if (!defined('DOKU_INC')) die();

class WorkflowProjectMetaDataAction extends ProjectMetadataAction {

    public function responseProcess() {
        $action = parent::getActionInstance($this->getActionName($this->params[ProjectKeys::KEY_ACTION]));
        $projectMetaData = $action->get($this->params);
        return $projectMetaData;
    }

    private function getActionName($action) {
        $actions = [ProjectKeys::KEY_EDIT => "GetProjectMetaDataAction",
                    ProjectKeys::KEY_IMPORT => "ImportProjectAction"
                   ];

        $ret = ($actions[$action]) ? $actions[$action]: "{$action}Action";
        return $ret;
    }

}
