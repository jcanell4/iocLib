<?php
if (!defined('DOKU_INC')) die();

class BasicWorkflowProjectAction extends ProjectAction {

    //[JOSEP] TODO => Rafa: En el fitxer workflow.json, cal poder associar una vista (nom) a alguns roles i/0 grups
    //                      amb accés al projecte, per cada action,
    //                      Aquí es recollirà la vista asociada (si existeix) i s'assignarà al model.
    public function responseProcess() {
        $action = parent::getActionInstance($this->getActionName($this->params[ProjectKeys::KEY_ACTION]));
        $projectMetaData = $action->get($this->params);
        $this->stateProcess($projectMetaData);
        return $projectMetaData;
    }

    protected function preResponseProcess() {
        parent::preResponseProcess();

        $model = $this->getModel();
        $projectData = $this->getDataProject();
        $projectRoles = ["responsable" => $projectData['responsable'],
                         "autor" => $projectData['autor'],
                         "revisor" => $projectData['revisor'],
                         "validador" => $projectData['validador']];
        $dp = $this->dataProject;       //[rol=>nombre]
        $rp = $this->roleProperties;    //[rol=>propiedad]
        $mdp = $model->dataProject;
        $mrp = $model->roleProperties;

        $id = $this->params[ProjectKeys::KEY_ID];
        $subSet = "management";

        $actionCommand = $model->getModelAttributes(AjaxKeys::KEY_ACTION);
        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($id, $subSet, $this->params['projectType']);

        $metaDataManagement = $metaDataQuery->getDataProject();
        $currentState = $metaDataManagement['workflow']['currentState'];
        $workflowJson = $model->getMetaDataJsonFile(FALSE, "workflow.json", $currentState);
        $views_rols = $workflowJson['actions'][$actionCommand]['views']['rols'];
        foreach ($views_rols as $rol => $vista) {
            if (in_array($rol, $projectRoles)) {
                $view = $vista;
                break;
            }
        }

        $view = NULL;
        if ($view) {
            $this->getModel()->setViewConfigName($view);
        }
    }

    protected function stateProcess(&$projectMetaData) {
        $model = $this->getModel();
        $id = $this->params[ProjectKeys::KEY_ID];
        $subSet = "management";

        $actionCommand = $model->getModelAttributes(AjaxKeys::KEY_ACTION);
        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($id, $subSet, $this->params['projectType']);

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

            $metaDataQuery->setMeta(json_encode($metaDataManagement), $subSet, "canvi d'estat");
            $message = self::generateInfo("info", "El canvi d'estat a '{$newState}' ha finalitzat correctament.", $id);
            $projectMetaData['info'] = self::addInfoToInfo($projectMetaData['info'], $message);
        }
    }

    protected function postResponseProcess(&$response) {
        parent::postResponseProcess($response);

        $model = $this->getModel();
        $id = $this->params[ProjectKeys::KEY_ID];
        $subSet = "management";

        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($id, $subSet, $this->params['projectType']);
        $metaDataManagement = $metaDataQuery->getDataProject();
        if (!isset($response[ProjectKeys::KEY_ID])) {
            $response[ProjectKeys::KEY_ID] = $this->idToRequestId($id);
        }
        $response[ProjectKeys::KEY_EXTRA_STATE] = [ProjectKeys::KEY_EXTRA_STATE_ID => "workflowState",
                                                   ProjectKeys::KEY_EXTRA_STATE_VALUE => $metaDataManagement['workflow']['currentState']];
    }

    private function getActionName($action) {
        $actions = [ProjectKeys::KEY_EDIT => "GetProjectAction"
                   ];
        $ret = ($actions[$action]) ? $actions[$action]: ucfirst($action)."ProjectAction";
        return $ret;
    }

}
