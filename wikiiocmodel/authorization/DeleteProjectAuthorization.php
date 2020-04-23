<?php
/**
 * DeleteProjectAuthorization: Extensión clase Autorización para los comandos
 * que precisan una autorización mínima de AUTH_DELETE y que el usuario sea del grupo "admin" o "projectmanager"
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class DeleteProjectAuthorization extends BasicCommandAuthorization {

    public function canRun() {
        if (parent::canRun()) {
            if (!$this->isUserGroup(array("admin"))) {
                $this->errorAuth['error'] = TRUE;
                $this->errorAuth['exception'] = 'UserNotAuthorizedException';
                $this->errorAuth['extra_param'] = $this->permission->getIdPage();
            }
        }
        return !$this->errorAuth['error'];
    }
}
