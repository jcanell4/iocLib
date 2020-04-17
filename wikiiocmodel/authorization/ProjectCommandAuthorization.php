<?php
/**
 * CommandAuthorization: define la clase de autorizaciones de los comandos del proyecto "documentation"
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ProjectCommandAuthorization extends BasicCommandAuthorization {

    protected $allowedRoles = [];

    public function setPermission($command) {
        parent::setPermission($command);
        $this->permission->setAuthor($command->getKeyDataProject(Permission::ROL_AUTOR));
        $this->permission->setResponsable($command->getKeyDataProject(Permission::ROL_RESPONSABLE));
        if ($this->isResponsable()) {
            $this->permission->setRol(Permission::ROL_RESPONSABLE);
        }else if ($this->isAuthor()) {
            $this->permission->setRol(Permission::ROL_AUTOR);
        }
    }

    public function isAuthor() {
        global $_SERVER;
        $ret = FALSE;
        if (($author = $this->permission->getAuthor())) {
            $ret = (in_array($_SERVER['REMOTE_USER'], $author));
        }
        return $ret;
    }

    public function isResponsable() {
        global $_SERVER;
        $ret = FALSE;
        if (($responsable = $this->permission->getResponsable())) {
            $ret = (in_array($_SERVER['REMOTE_USER'], $responsable));
        }
        return $ret;
    }

    public function getAllowedRoles() {
        return $this->allowedRoles;
    }

}