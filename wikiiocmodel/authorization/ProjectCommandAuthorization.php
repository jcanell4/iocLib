<?php
/**
 * CommandAuthorization: define la clase de autorizaciones de los comandos del proyecto "documentation"
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ProjectCommandAuthorization extends BasicCommandAuthorization {

    protected $allowedRoles = [];

    public function __construct() {
        parent::__construct();
//        $this->allowedGroups = ["admin"];
        $this->allowedRoles = [Permission::ROL_RESPONSABLE];
    }

    public function canRun($permis=AUTH_NONE, $type_exception="Command") {   //$permis => permís mínim a més de pertanyer als grups i als roles establerts
        if (parent::canRun()) {
            if ($permis > AUTH_NONE && $this->permission->getInfoPerm() < $permis) {
                $this->errorAuth['error'] = TRUE;
                $this->errorAuth['exception'] = 'InsufficientPermissionTo'.$type_exception.'ProjectException';
                $this->errorAuth['extra_param'] = $this->permission->getIdPage();
            }else {
                if (!$this->isUserGroup($this->allowedGroups) && !$this->isUserRole($this->allowedRoles)) {
                    $this->errorAuth['error'] = TRUE;
                    $this->errorAuth['exception'] = 'UserNotAuthorizedException';
                    $this->errorAuth['extra_param'] = $this->permission->getIdPage();
                }
            }
        }
        return !$this->errorAuth['error'];
    }

    public function setPermission($command) {
        parent::setPermission($command);
        $this->permission->setAllRoleMembers($command->getKeyDataProject());
        $this->assignRoleFromUserToPermission();
        //ALERTA Rafa, Cal adaptar aquestes assignacions per tal de fer-ho tot més genèric. 
        //A línia 37 s'assignen tots els membres dels diferents roles d'un projecte amb independència 
        // de si es coneixen el rols o no. A la línia 38 s'extreuen, també a cegues, els roles de l'usuari i
        // s'assignen a $this->permission.
        $this->permission->setAuthor($command->getKeyDataProject(Permission::ROL_AUTOR));
        $this->permission->setResponsable($command->getKeyDataProject(Permission::ROL_RESPONSABLE));
        //Ja no cal. S'assignen a la línia 38
//        if ($this->isResponsable()) {
//            $this->permission->setRol(Permission::ROL_RESPONSABLE);
//        }
//        if ($this->isAuthor()) {
//            $this->permission->setRol(Permission::ROL_AUTOR);
//        }
    }
    
    public function assignRoleFromUserToPermission(){
        $roles = $this->permission->getRoleMembers();
        foreach ($roles as $role => $members) {
            if($this->isRoleMember($role)){
                $this->permission->setRol($role);
            }
        }                
    }

    public function isRoleMember($role) {
        global $_SERVER;
        $ret = FALSE;
        if (($members = $this->permission->getRoleMembers($role))) {
            $ret = (in_array($_SERVER['REMOTE_USER'], $members));
        }
        return $ret;
        
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
