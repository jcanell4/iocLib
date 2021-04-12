<?php
/**
 * BasicCommandAuthorization: define la clase general de autorizaciones de los comandos
  * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
require_once (DOKU_INC . "inc/auth.php");
require_once (DOKU_INC . "inc/common.php");

class BasicCommandAuthorization extends AbstractCommandAuthorization {

    public function __construct() {
        parent::__construct();
    }

    protected function getPermissionInstance() {
        return $this->permission;
    }

    public function setPermissionInstance($permission) {
        $this->permission = $permission;
    }

    public function setPermission($command) {
        parent::setPermission($command);
        $this->permission->setIdPage($command->getParams('id'));
        $userinfo = WikiIocInfoManager::getInfo('userinfo');
        if (is_array($userinfo)){
            $this->permission->setUserGroups($userinfo['grps']);
        }
        $this->permission->setInfoPerm(WikiIocInfoManager::getInfo('perm'));
    }

    // pendent de convertir a private quan no l'utilitzi login_command
    public function isUserAuthenticated($userId=NULL) {
        global $_SERVER;

        if ($userId) {
            return $_SERVER['REMOTE_USER'] === $userId;
        }else {
            return $_SERVER['REMOTE_USER'] ? TRUE : FALSE;
        }
    }

    /**
     * Comproba si el token de seguretat està verificat, fent servir una funció de la DokuWiki.
     * @return bool
    */
    public function isSecurityTokenVerified() {
        return checkSecurityToken();
    }

    /**
     * Verifica si alguno de los grupos a los que pertenece el usuario coincide con alguno de los grupos permitidos
     * @param array $grups : grupos a los que pertenece el usuario
     * @return boolean
     */
    public function isUserGroup($grups=[]) {
        $ret = FALSE;
        if (!empty($grups)) {
            $userGrups = $this->permission->getUserGroups();
            if (!empty($userGrups)) {
                if (!is_array($userGrups)) {
                    $userGrups = [$userGrups];
                }
                foreach ($grups as $grup) {
                    $ret |= in_array($grup, $userGrups);
                }
            }
        }
        return $ret;
    }

    /**
     * Verifica si alguno de los roles del usuario coincide con alguno de los roles permitidos
     * @param array $roles : roles del usuario
     * @return boolean
     */
    public function isUserRole($roles=[]) {
        $ret = FALSE;
        if (!empty($roles)) {
            $userRoles = $this->permission->getRol();
            if (!empty($userRoles)) {
                if (!is_array($userRoles)) {
                    $userRoles = [$userRoles];
                }
                foreach ($roles as $role) {
                    $ret |= in_array($role, $userRoles);
                }
            }
        }
        return $ret;
    }

}
