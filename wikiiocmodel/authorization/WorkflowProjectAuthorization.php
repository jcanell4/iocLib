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

        $dataProject = $model->getCurrentDataProject("management");
        $dadesControl = $model->getProjectControls(FALSE, "workflow");

        $this->allowedGroups = ["admin"];
        $this->allowedRoles = [Permission::ROL_RESPONSABLE];
    }

}
