<?php
/**
 * ManagerProjectAuthorization: Extensión clase Authorization para los comandos
 * que precisan una autorización mínima de AUTH_DELETE y que el usuario sea del grupo "admin" o "projectmanager"
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ManagerProjectAuthorization extends ProjectCommandAuthorization {

    public function __construct() {
        parent::__construct();
        $this->allowedGroups[] = "manager";
        $this->allowedGroups[] = "projectmanager";
        $this->allowedRoles = [];
    }

    public function canRun($permis=AUTH_DELETE, $type_exception="Edit") {
        parent::canRun($permis, $type_exception);
        return !$this->errorAuth['error'];
    }
}
