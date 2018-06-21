<?php
/**
 * AbstractCommandAuthorization: define la clase de autorizaciones de los comandos
 * @author Rafael Claver
 */
if (!defined('DOKU_INC') ) die();

abstract class AbstractCommandAuthorization implements AuthorizationKeys{

    protected $permission;
    protected $errorAuth = array(
                              self::ERROR_KEY => TRUE
                             ,self::EXCEPTION_KEY => ''
                             ,self::EXTRA_PARAM_KEY => NULL
                           );

    public function __construct() {}

    abstract public function setPermissionInstance($permission);
    abstract protected function getPermissionInstance();

    /**
     * Responde a la pregunta: ¿los permisos permiten la ejecución del comando?
     * @return bool. Indica si se han obtenido, o no, los permisos generales
     */
    public function canRun() {
        $this->errorAuth[self::ERROR_KEY] = FALSE;
        $this->errorAuth[self::EXCEPTION_KEY] =  '';
        $this->errorAuth[self::EXTRA_PARAM_KEY] = NULL;

        if ($this->permission->getAuthenticatedUsersOnly()) {
            if (($this->errorAuth[self::ERROR_KEY] = !$this->permission->getSecurityTokenVerified())){
                $this->errorAuth[self::EXCEPTION_KEY] = 'AuthorizationNotTokenVerified';
            } else {
                if (($this->errorAuth[self::ERROR_KEY] = !$this->permission->getUserAuthenticated())) {
                    $this->errorAuth[self::EXCEPTION_KEY] = 'AuthorizationNotUserAuthenticated';
                } else {
                    if (($this->errorAuth[self::ERROR_KEY] = !$this->isCommandAllowed())){
                        $this->errorAuth[self::EXCEPTION_KEY] = 'AuthorizationNotCommandAllowed';
                    }
                }
            }
        }
        return !$this->errorAuth[self::ERROR_KEY];
    }

    public function getPermission() {
        return $this->permission;
    }

    public function getAuthorizationError($key) {
        return $this->errorAuth[$key];
    }

    public function setPermission($command) {
        WikiIocInfoManager::setIsMediaAction($command->getNeedMediaInfo());
        WikiIocInfoManager::setParams($command->getParams());
        $this->_setPermission($command);
    }

    private function _setPermission($command) {
        $this->permission->setPermissionFor($command->getPermissionFor());
        $this->permission->setAuthenticatedUsersOnly($command->getAuthenticatedUsersOnly());
        $this->permission->setSecurityTokenVerified($this->isSecurityTokenVerified());
        $this->permission->setUserAuthenticated($this->isUserAuthenticated());
        $this->permission->setInfoWritable(WikiIocInfoManager::getInfo('writable'));
        $this->permission->setInfoIsadmin(WikiIocInfoManager::getInfo('isadmin'));
        $this->permission->setInfoIsmanager(WikiIocInfoManager::getInfo('ismanager'));
        $this->permission->setIsValidUser($this->isValidUser($command->getParams('user')));
        $this->permission->setUserGroups(array());
    }

    private function isCommandAllowed() {
        $permissionFor = $this->permission->getPermissionFor();
        $grup = $this->permission->getUserGroups();
        $found = sizeof($permissionFor) == 0 || !is_array($grup);
        for($i = 0; !$found && $i < sizeof($grup); $i++) {
            $found = in_array($grup[$i], $permissionFor);
        }
        return $found;
    }

    private function isValidUser($user) {
        return $_SERVER['REMOTE_USER'] === $user;
    }

}
