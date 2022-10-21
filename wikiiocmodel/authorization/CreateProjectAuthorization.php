<?php
/**
 * CreateProjectAuthorization: Extensión clase Autorización para los comandos
 * que precisan una autorización mínima de AUTH_CREATE y que el usuario sea del grupo "admin" o "projectmanager"
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class CreateProjectAuthorization extends ProjectCommandAuthorization {

    public function __construct() {
        parent::__construct();
        $this->allowedGroups[] = "projectmanager";
        $this->allowedRoles = [];
    }

    public function canRun($permis=AUTH_CREATE, $type_exception="Create") {
        parent::canRun($permis, $type_exception);
        return !$this->errorAuth['error'];
    }
}
