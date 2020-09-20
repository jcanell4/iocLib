<?php
/**
 * SupervisorProjectAuthorization: Extensión clase Autorización para los proyectos
 *                                 que tienen atributo de supervisor
  * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ResponsableProjectAuthorization extends ProjectCommandAuthorization {
     public function __construct() {
        parent::__construct();
        $this->allowedRoles[] = Permission::ROL_RESPONSABLE;
    }

//    public function canRun($permis=AUTH_EDIT, $type_exception="Edit") {
////        if (parent::canRun()) {
////            if (($this->permission->getInfoPerm() < AUTH_EDIT || !$this->isUserGroup(["admin"])) && !$this->isResponsable()) {
////                $this->errorAuth['error'] = TRUE;
////                $this->errorAuth['exception'] = 'InsufficientPermissionToEditProjectException';
////                $this->errorAuth['extra_param'] = $this->permission->getIdPage();
////            }
////        }
//        parent::canRun($permis, $type_exception);
//        return !$this->errorAuth['error'];
//    }
}
