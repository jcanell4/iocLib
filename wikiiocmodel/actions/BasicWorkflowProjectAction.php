<?php
if (!defined('DOKU_INC')) die();

class BasicWorkflowProjectAction extends ProjectAction {

    public function responseProcess() {
        $action = parent::getActionInstance($this->getActionName($this->params[ProjectKeys::KEY_ACTION]));
        $projectMetaData = $action->get($this->params);
        $this->stateProcess($projectMetaData);
        return $projectMetaData;
    }

    protected function preResponseProcess() {
        parent::preResponseProcess();

        $model = $this->getModel();

        $user_roles = (is_array($this->params['roles'])) ? $this->params['roles'] : [$this->params['roles']];
        $user_groups = $this->params['groups'];

        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($this->params[ProjectKeys::KEY_ID], "management", $this->params[ProjectKeys::KEY_PROJECT_TYPE]);
        $currentState = $metaDataQuery->getDataProject()['workflow']['currentState'];
        $actionCommand = $model->getModelAttributes(AjaxKeys::KEY_ACTION);
        $action = $model->getMetaDataActionWorkflowFile($currentState, $actionCommand);

        //busca en el apartado views si se ha especificado el rol del usuario actual
        $views_rols = $action['views']['rols'];
        foreach ($views_rols as $r => $vista) {
            if (in_array($r, $user_roles)) {
                $view = $vista;
                break;
            }
        }
        if (!$view) {
           //busca en el apartado views si se ha especificado el grupo del usuario actual
            $views_group = $action['views']['groups'];
            foreach ($views_group as $g => $vista) {
                if (in_array($g, $user_groups)) {
                    $view = $vista;
                    break;
                }
            }
        }
        $file = $model->getProjectMetaDataQuery()->getProjectTypeDir()."metadata/config/{$view}.json";
        if ($view && is_file($file)) {
            $this->getModel()->setViewConfigName($view);
        }
    }

    protected function stateProcess(&$projectMetaData) {
        $model = $this->getModel();
        $id = $this->params[ProjectKeys::KEY_ID];
        $subSet = "management";

        $actionCommand = $model->getModelAttributes(AjaxKeys::KEY_ACTION);
        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($id, $subSet, $this->params[ProjectKeys::KEY_PROJECT_TYPE]);

        $metaDataManagement = $metaDataQuery->getDataProject();
        $currentState = $metaDataManagement['workflow']['currentState'];
        $workflowJson = $model->getMetaDataJsonFile(FALSE, "workflow.json", $currentState);
        $newState = ($workflowJson['actions'][$actionCommand]['changeStateTo']) ? $workflowJson['actions'][$actionCommand]['changeStateTo'] : $currentState;

        if ($currentState !== $newState) {
            $newMetaData['changeDate'] = date("Y-m-d");
            $newMetaData['oldState'] = $currentState;
            $newMetaData['newState'] = $newState;
            $newMetaData['changeAction'] = $actionCommand;
            $newMetaData['user'] = WikiIocInfoManager::getInfo("userinfo")['name'];

            $metaDataManagement['stateHistory'][] = $newMetaData;
            $metaDataManagement['workflow']['currentState'] = $newState;

            $metaDataQuery->setMeta(json_encode($metaDataManagement), $subSet, "canvi d'estat", NULL);
            $message = self::generateInfo("info", "El canvi d'estat a '{$newState}' ha finalitzat correctament.", $id);
            $projectMetaData['info'] = self::addInfoToInfo($projectMetaData['info'], $message);
        }
        $message = self::generateInfo("info", "L'estat actual és '{$newState}'.", $id);
        $projectMetaData['info'] = self::addInfoToInfo($projectMetaData['info'], $message);
    }

    protected function postResponseProcess(&$response) {
        parent::postResponseProcess($response);

        $model = $this->getModel();
        $id = $this->params[ProjectKeys::KEY_ID];
        $subSet = "management";

        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($id, $subSet, $this->params[ProjectKeys::KEY_PROJECT_TYPE]);
        $metaDataManagement = $metaDataQuery->getDataProject();
        if (!isset($response[ProjectKeys::KEY_ID])) {
            $response[ProjectKeys::KEY_ID] = $this->idToRequestId($id);
        }
        $response[ProjectKeys::KEY_EXTRA_STATE] = [ProjectKeys::KEY_EXTRA_STATE_ID => "workflowState",
                                                   ProjectKeys::KEY_EXTRA_STATE_VALUE => $metaDataManagement['workflow']['currentState']];
    }

    private function getActionName($action) {
        $actions = [ProjectKeys::KEY_EDIT => "GetProjectAction",
                    ProjectKeys::KEY_SAVE => "SetProjectAction"
                   ];
        $ret = ($actions[$action]) ? $actions[$action]: ucfirst($action)."ProjectAction";
        return $ret;
    }

}
