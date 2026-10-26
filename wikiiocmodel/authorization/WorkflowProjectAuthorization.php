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
        $params = $model->getModelAttributes();

        $metaDataQuery = $model->getPersistenceEngine()->createProjectMetaDataQuery($params[ProjectKeys::KEY_ID], $params[ProjectKeys::KEY_METADATA_SUBSET], $params[ProjectKeys::KEY_PROJECT_TYPE]);
        $data = $metaDataQuery->getDataProject();
        $action = $model->getMetaDataActionWorkflowFile($data['workflow']['currentState'], $params[AjaxKeys::KEY_ACTION]);

        $this->allowedGroups = $action['permissions']['groups'];
        $this->allowedRoles = $action['permissions']['rols'];
    }

}
