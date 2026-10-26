<?php
/**
 * Permission: clase genérica que gestiona los permisos de usuario en un proyecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC') ) die();

class BasicPermission extends AbstractPermission {

    protected $info_perm;
    protected $resourceExist;

    private $overwriteRequired;
    private $isMyOwnNs;
    private $isEmptyText;

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

    public function getOverwriteRequired() {
        return $this->overwriteRequired;
    }

    public function setOverwriteRequired($overwriteRequired) {
        $this->overwriteRequired = $overwriteRequired;
    }

    public function getIsMyOwnNs() {
        return $this->isMyOwnNs;
    }

    public function setIsMyOwnNs($isMyOwnNs) {
        $this->isMyOwnNs = $isMyOwnNs;
    }

    public function getIsEmptyText() {
        return $this->isEmptyText;
    }

    public function setIsEmptyText($isEmptyText) {
        $this->isEmptyText = $isEmptyText;
    }

}
