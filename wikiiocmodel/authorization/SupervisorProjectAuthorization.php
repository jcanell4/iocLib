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
        $this->allowedRoles[] = ProjectPermission::ROL_AUTOR;
        $this->allowedRoles[] = ProjectPermission::ROL_SUPERVISOR;
    }

    public function setPermission($command) {
        $this->permission->setSupervisor($command->getKeyDataProject(ProjectPermission::ROL_SUPERVISOR));

        if ($this->isSupervisor()) {
            $this->permission->setRol(ProjectPermission::ROL_SUPERVISOR);
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
