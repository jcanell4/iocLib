<?php
/**
 * GenerateProjectAuthorization: Extensión clase Autorización para los comandos
 * que precisan una autorización mínima de AUTH_CREATE y que el usuario sea del grupo "admin" o "projectmanager"
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
require_once (DOKU_INC . 'inc/auth.php');

class GenerateProjectAuthorization extends ProjectCommandAuthorization {

    public function __construct() {
        parent::__construct();
        $this->allowedGroups[] = "projectmanager";
        $this->allowedRoles[] = ProjectPermission::ROL_AUTOR;
    }

    public function canRun($permis=AUTH_NONE, $type_exception="Generate") {
        parent::canRun($permis, $type_exception);
        return !$this->errorAuth['error'];
    }
}
