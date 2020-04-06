<?php
/**
 * Permission: clase genérica que gestiona los permisos de usuario en un proyecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC') ) die();

class BasicPermission extends AbstractPermission {

    protected $info_perm;
    protected $resourceExist;

    public function getInfoPerm() {
        return $this->info_perm;
    }

    public function getResourceExist() {
        return $this->resourceExist;
    }

    public function isReadOnly(){
        return ($this->getInfoPerm() < AUTH_EDIT);
    }

    public function setInfoPerm($info_perm) {
        $this->info_perm = $info_perm;
    }

    public function setResourceExist($resourceExist) {
        $this->resourceExist = $resourceExist;
    }

}
