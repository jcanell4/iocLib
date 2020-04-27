<?php
/**
 * SupervisorProjectAuthorization: Extensión clase Autorización para los proyectos
 *                                 que tienen atributo de supervisor
  * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class SupervisorProjectAuthorization extends ProjectCommandAuthorization {

    public function __construct() {
        parent::__construct();
        $this->allowedGroups[] = "manager";
        $this->allowedRoles[] = Permission::ROL_AUTOR;
        $this->allowedRoles[] = Permission::ROL_SUPERVISOR;
    }

//    public function canRun() {
//        if (parent::canRun()) {
//            if (!$this->isUserGroup($this->allowedGroups) && !$this->isUserRole($this->allowedRoles)) {
//                $this->errorAuth['error'] = TRUE;
//                $this->errorAuth['exception'] = 'InsufficientPermissionToEditProjectException';
//                $this->errorAuth['extra_param'] = $this->permission->getIdPage();
//            }
//        }
//        return !$this->errorAuth['error'];
//    }

    public function setPermission($command) {
        $this->permission->setSupervisor($command->getKeyDataProject(Permission::ROL_SUPERVISOR));

        if ($this->isSupervisor()) {
            $this->permission->setRol(Permission::ROL_SUPERVISOR);
        }
        parent::setPermission($command);
    }

    public function isSupervisor() {
        global $_SERVER;
        $supervisor = $this->permission->getSupervisor();

        if ($supervisor) {
            $ret = (in_array($_SERVER['REMOTE_USER'], $supervisor));
        }
        return $ret;
    }

}
