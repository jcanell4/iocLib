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
//        if (parent::canRun()) {
//            if ( $this->permission->getInfoPerm() < AUTH_DELETE || !$this->isUserGroup(["projectmanager","admin","manager"]) ) {
//                $this->errorAuth['error'] = TRUE;
//                $this->errorAuth['exception'] = 'InsufficientPermissionToEditProjectException';
//                $this->errorAuth['extra_param'] = $this->permission->getIdPage();
//            }
//        }
        parent::canRun($permis, $type_exception);
        return !$this->errorAuth['error'];
    }
}
