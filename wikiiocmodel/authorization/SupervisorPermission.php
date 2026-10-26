<?php
/**
 * SupervisorPermission: amplía la gestión de los permisos de usuario como supervisor
 * @author Rafael Claver
 */
if (!defined('DOKU_INC') ) die();

class SupervisorPermission extends ProjectPermission {

    protected $supervisor;   //array

    public function getSupervisor() {
        return $this->supervisor;
    }

    public function setSupervisor($supervisor) {
        if (is_string($supervisor) && !empty($supervisor)){
            $this->supervisor = preg_split("/[\s,]+/", $supervisor);
        }
    }

}
