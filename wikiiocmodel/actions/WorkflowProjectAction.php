<?php
if (!defined('DOKU_INC')) die();

class WorkflowProjectAction extends ProjectAction {

    public function responseProcess() {
        $action = parent::getActionInstance($this->getActionName($this->params[ProjectKeys::KEY_ACTION]));
        $projectMetaData = $action->get($this->params);
        $response = $this->satateProcess();
        $projectMetaData['info'] = self::addInfoToInfo($projectMetaData['info'], $response);
        return $projectMetaData;
    }

    protected function satateProcess() {
        $model = $this->getModel();
        $id = $this->params[ProjectKeys::KEY_ID];
        $subSet = "management";

        $actionCommand = $model->getModelAttributes(AjaxKeys::KEY_ACTION);
        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($id, $subSet, $this->params['projectType']);

        $metaDataManagement = $metaDataQuery->getDataProject();   //$metaDataManagement = ["workflow" => ["currentState" => "creating"]]
        $currentState = $metaDataManagement['workflow']['currentState'];
        $workflowJson = $model->getMetaDataJsonFile(FALSE, "workflow.json", $currentState);  //$workflowJson contiene todo el array de workflow.json
        $newState = ($workflowJson['actions'][$actionCommand]['changeStateTo']) ? $workflowJson['actions'][$actionCommand]['changeStateTo'] : $currentState;

        if ($currentState !== $newState) {
            $newMetaData['changeDate'] = date("Y-m-d");
            $newMetaData['oldState'] = $currentState;
            $newMetaData['newState'] = $newState;
            $newMetaData['changeAction'] = $actionCommand;
            $newMetaData['user'] = WikiIocInfoManager::getInfo("userinfo")['name'];

            $metaDataManagement['stateHistory'][] = $newMetaData;
            $metaDataManagement['workflow']['currentState'] = $newState;

            $metaDataQuery->setMeta(json_encode($metaDataManagement), $subSet, "canvi d'estat");
            $response = self::generateInfo("info", "El canvi d'estat a '{$newState}' ha finalitzat correctament.", $id);
        }else {
            $response = self::generateInfo("info", "No hi ha canvi d'estat.", $id);
        }
        return $response;
    }

    protected function postResponseProcess(&$response) {
        parent::postResponseProcess($response);

        $model = $this->getModel();
        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($this->params[ProjectKeys::KEY_ID], "management", $this->params['projectType']);
        $metaDataManagement = $metaDataQuery->getDataProject();
        $response[ProjectKeys::KEY_EXTRA_STATE] = [ProjectKeys::KEY_EXTRA_STATE_ID => "workflowState", ProjectKeys::KEY_EXTRA_STATE_VALUE => $metaDataManagement['workflow']['currentState']];
    }

    private function getActionName($action) {
        $actions = [ProjectKeys::KEY_EDIT => "GetProjectAction"
                   ];
        $ret = ($actions[$action]) ? $actions[$action]: ucfirst($action)."ProjectAction";
        return $ret;
    }

}
