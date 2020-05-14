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
        $this->allowedRoles = [];
    }

    public function canRun($permis=AUTH_CREATE, $type_exception="Generate") {
//        if (parent::canRun()) {
//            if ($this->permission->getInfoPerm() < AUTH_CREATE) {
//                $this->errorAuth['error'] = TRUE;
//                $this->errorAuth['exception'] = 'InsufficientPermissionToGenerateProjectException';
//                $this->errorAuth['extra_param'] = $this->permission->getIdPage();
//            }else {
//                if (!$this->isUserGroup(array("projectmanager","admin"))) {
//                    $this->errorAuth['error'] = TRUE;
//                    $this->errorAuth['exception'] = 'UserNotAuthorizedException';
//                    $this->errorAuth['extra_param'] = $this->permission->getIdPage();
//                }
//            }
//        }
        parent::canRun($permis, $type_exception);
        return !$this->errorAuth['error'];
    }
}
