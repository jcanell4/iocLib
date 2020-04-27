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

    public function canRun() {
//        if (parent::canRun()) {
//            if ($this->permission->getInfoPerm() < AUTH_CREATE) {
//                $this->errorAuth['error'] = TRUE;
//                $this->errorAuth['exception'] = 'InsufficientPermissionToCreateProjectException';
//                $this->errorAuth['extra_param'] = $this->permission->getIdPage();
//            }else {
//                if (!$this->isUserGroup(array("projectmanager","admin"))) {
//                    $this->errorAuth['error'] = TRUE;
//                    $this->errorAuth['exception'] = 'UserNotAuthorizedException';
//                    $this->errorAuth['extra_param'] = $this->permission->getIdPage();
//                }
//            }
//        }
        parent::canRun(AUTH_CREATE, "Create");
        return !$this->errorAuth['error'];
    }
}
