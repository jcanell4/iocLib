<?php
if (!defined('DOKU_INC')) die();

class BasicWorkflowProjectAction extends ProjectAction {

    public function responseProcess() {
        if ($this->params[ProjectKeys::KEY_ACTION] === ProjectKeys::KEY_VIEW){
            $this->params["data-call"]= "project&do=workflow&action=view";
        }
        $action = parent::getActionInstance($this->getActionName($this->params[ProjectKeys::KEY_ACTION]));
        if ($this->params[ProjectKeys::KEY_ACTION] === ProjectKeys::KEY_SAVE){
            $action->addExcludeKeys(["action", "roles", "groups"]);
        }        
        $response = $action->get($this->params);
        $response["alternativeResponseHandler"] = $this->getAlternativeResponseHandler();
        $this->stateProcess($response);
        return $response;
    }
    
    private function getAlternativeResponseHandler(){
        $ret=NULL;
        $name = ucfirst($this->params[ProjectKeys::KEY_ACTION]);
        $prDir = $this->getModel()->getProjectMetaDataQuery()->getProjectTypeDir();
        $tplName = WikiGlobalConfig::tplIncName();
        $path = "{$prDir}command/responseHandler/$tplName/{$name}ResponseHandler.php";
        if(file_exists($path)){
            $rhname =  "{$name}ResponseHandler";
            require_once($path);
            $ret = new $rhname($name);
        }
        return $ret;
    }

    protected function preResponseProcess() {
        $model = $this->getModel();
        $user_roles = (is_array($this->params['roles'])) ? $this->params['roles'] : [$this->params['roles']];
        $user_groups = $this->params['groups'];

        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($this->params[ProjectKeys::KEY_ID], "management", $this->params[ProjectKeys::KEY_PROJECT_TYPE]);
        $currentState = $metaDataQuery->getDataProject()['workflow']['currentState'];
        $actionCommand = $model->getModelAttributes(AjaxKeys::KEY_ACTION);
        $action = $model->getMetaDataActionWorkflowFile($currentState, $actionCommand);

        //busca en el apartado views si se ha especificado el rol del usuario actual
        if (($views_rols = $action['views']['rols'])) {
            foreach ($views_rols as $r => $vista) {
                if (in_array($r, $user_roles)) {
                    $view = $vista;
                    break;
                }
            }
        }
        if (!$view) {
           //busca en el apartado views si se ha especificado el grupo del usuario actual
            if (($views_group = $action['views']['groups'])) {
                foreach ($views_group as $g => $vista) {
                    if (in_array($g, $user_groups)) {
                        $view = $vista;
                        break;
                    }
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
        if ($this->params[ProjectKeys::KEY_ACTION] === ProjectKeys::KEY_RENAME && $this->params[ProjectKeys::KEY_NEWNAME]) {
            $path = substr($this->params[ProjectKeys::KEY_ID], 0, strrpos($this->params[ProjectKeys::KEY_ID], ":"));
            $this->params[ProjectKeys::KEY_ID] = "$path:{$this->params[ProjectKeys::KEY_NEWNAME]}";
        }
        $id = $this->params[ProjectKeys::KEY_ID];
        $subSet = "management";

        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($id, $subSet, $this->params[ProjectKeys::KEY_PROJECT_TYPE]);
        $actionCommand = $model->getModelAttributes(AjaxKeys::KEY_ACTION);
        $metaDataManagement = $metaDataQuery->getDataProject($id);
        $currentState = $metaDataManagement['workflow']['currentState'];
        $workflowJson = $model->getCurrentWorkflowActionAttributes($currentState, $actionCommand);
        $newState = ($workflowJson['changeStateTo']) ? $workflowJson['changeStateTo'] : $currentState;
        $remarks = $projectMetaData['projectMetaData']['cc_raonsModificacio'];
        $this->model->stateProcess($id, $metaDataQuery, $newState, $remarks, $subSet);

        $msgState = WikiIocLangManager::getLang('workflowState')[$newState];
        if ($currentState !== $newState) {
            $message = self::generateInfo("info", "El canvi d'estat a '{$msgState}' ha finalitzat correctament.", $id);
            $projectMetaData['info'] = self::addInfoToInfo($projectMetaData['info'], $message);
        }
        $message = self::generateInfo("info", "L'estat actual és: '{$msgState}'.", $id);
        $projectMetaData['info'] = self::addInfoToInfo($projectMetaData['info'], $message);
    }

    protected function postResponseProcess(&$response) {
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
                    ProjectKeys::KEY_SAVE => "SetProjectAction",
                    ProjectKeys::KEY_FTP_PROJECT => "FtpProjectAction"
                   ];
        $ret = ($actions[$action]) ? $actions[$action]: ucfirst($action)."ProjectAction";
        return $ret;
    }

}
