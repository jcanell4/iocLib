<?php
/**
 * SaveProjectAuthorization: Extensión clase Autorización para los comandos
 *      con una autorización por roles y grupos
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class SaveProjectAuthorization extends EditProjectAuthorization {

    public function canRun($permis=AUTH_NONE, $type_exception="Save") {
        if (parent::canRun($permis, $type_exception)) {
            $permission = $this->getPermission();
            if ($permission->isRoleChanged() && !$this->isResponsable()) {
                $this->errorAuth['error'] = TRUE;
                $this->errorAuth['exception'] = 'ResponsableNotVerifiedException';
                $this->errorAuth['extra_param'] = $permission->getIdPage();
            }
        }
        return !$this->errorAuth['error'];
    }

    public function setPermission($command) {
        parent::setPermission($command);
        $this->getPermission()->setRoleChanged($this->_isRoleChanged($command));
    }

    /**
     * Comprueba si se ha modificado alguno de los roles del proyecto (campos establecidos como tipo rol).
     * Lo hace comparando los roles obtenidos de la autorización (antes de la modificación) con los datos del formulario recibido
     * @param object $command
     * @return boolean
     */
    private function _isRoleChanged($command) {
        $oldProjectRoles = $command->getKeyDataProject();  //roles establecidos en el proyecto
        $params = $command->getParams(); //datos del formulario recibido
        $changed = FALSE;
        foreach ($oldProjectRoles as $rol => $name) {
            if ($params[$rol] !== $name) {
                $changed = TRUE;
                break;
            }
        }
        return $changed;
    }

}
