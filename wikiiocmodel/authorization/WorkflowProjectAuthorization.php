<?php
/**
 * WorkflowProjectAuthorization: define la clase de autorizaciones de los comandos del proyecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class WorkflowProjectAuthorization extends ProjectCommandAuthorization {

    public function __construct() {
        global $plugin_controller;
        parent::__construct();
        $model = $plugin_controller->getCurrentProjectModel("management");

        $dataProject = $model->getCurrentDataProject("management", FALSE);
        $state = $dataProject['workflow']['currentState'];

        $jsonConfig = $model->getMetaDataJsonFile(FALSE, "workflow.json", $state);
        $actionCommand = $model->getModelAttributes(AjaxKeys::KEY_ACTION);
        $permissions = $jsonConfig['actions'][$actionCommand]['permissions'];

        $this->allowedGroups = $permissions['groups'];
        $this->allowedRoles = $permissions['rols'];
    }

}
