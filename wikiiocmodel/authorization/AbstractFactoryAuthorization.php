<?php
/**
 * AbstractFactoryAuthorization: carga las clases de autorización de los comandos de los proyectos
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

abstract class AbstractFactoryAuthorization {

    protected $projectAuth; //ruta de las autorizaciones particular del proyecto
    protected $defaultAuth; //array de rutas por defecto de las autorizaciones
    protected $authCfg = array();

    protected function __construct($projectAuth=NULL) {
        $this->projectAuth = $projectAuth;
    }

    public static function Instance($defaultAuth=NULL){
        static $inst = NULL;
        if ($inst === NULL) {
            $inst = new FactoryAuthorization();
            $inst->defaultAuth = $defaultAuth;
        }
        return $inst;
    }

    public function createAuthorizationManager($str_cmd) {

        $fileAuthorization = $this->_createAuthorization($str_cmd, $this->projectAuth);

        if ($fileAuthorization === NULL && $this->defaultAuth) {
            foreach ($this->defaultAuth as $pathAuth) {
                if ($fileAuthorization === NULL) {
                    $fileAuthorization = $this->_createAuthorization($str_cmd, $pathAuth);
                }
            }
        }

        if ($fileAuthorization === NULL) {
            $this->setAuthorizationCfg();
            $fileAuthorization = $this->readFileIn2CaseFormat($this->authCfg['_default'], 'authorization', $this->projectAuth);
        }

        if ($fileAuthorization === NULL) {
            throw new AuthorizationNotCommandAllowed("authorization file not supplied.");
        }

        $authorization = new $fileAuthorization();
        $authorization->setPermissionInstance($this->_createPermissionClass($authorization));
        return $authorization;
    }

    private function _createPermissionClass($authorization) {
        $file = $this->projectAuth . "Permission.php";
        if (!file_exists($file) && $this->defaultAuth) {
            foreach ($this->defaultAuth as $pathAuth) {
                if (!file_exists($file)) {
                    $file = $pathAuth . "Permission.php";
                }
            }
        }

        if (file_exists($file)) {
            include_once $file;
            $permis = new Permission($authorization);
        }else{
            $permis = new BasicPermission($authorization);
        }
        $ret = &$permis;
        return $ret;
    }

    private function _createAuthorization($str_cmd, $pathAuth) {
        $fileAuthorization = $this->readFileIn2CaseFormat($str_cmd, 'authorization', $pathAuth);
        if ($fileAuthorization === NULL) {
            $this->setAuthorizationCfg();
            if ($this->authCfg[$str_cmd]) {
                $fileAuthorization = $this->readFileIn2CaseFormat($this->authCfg[$str_cmd], 'authorization', $pathAuth);
            }
        }
        return $fileAuthorization;
    }

    /* Carga el archivo correspondiente al comando,
     * buscándolo por el nombre en formato convencional y en formato CamelCase
     */
    private function readFileIn2CaseFormat($str_cmd, $part2, $pathAuth) {
        $name = $this->nameCaseFormat($str_cmd, $part2,'_');
        $ret = NULL;
        $authFile = "$pathAuth$name.php";
        if (!file_exists($authFile)) {
            $name = $this->nameCaseFormat($str_cmd, $part2,'camel');
            $authFile = "$pathAuth$name.php";
        }
        if (!file_exists($authFile)) {
            $name = $this->nameCaseFormat($str_cmd, $part2,'camel2');
            $authFile = "$pathAuth$name.php";
        }
        if (file_exists($authFile)) {
            require_once($authFile);
            $ret = $name;
        }
        return $ret;
    }

    /* Devuelve un nombre compuesto en el formato solicitado:
     * 'guion_bajo' o CamelCase
     */
    private function nameCaseFormat($part1, $part2, $case) {
        if ($case === 'camel') {
            $ret = strtoupper(substr($part1, 0, 1)) . substr($part1, 1)
                 . strtoupper(substr($part2, 0, 1)) . substr($part2, 1);
        }elseif ($case === 'camel2') {
            $ret = strtoupper(substr($part1, 0, 1)) . strtolower(substr($part1, 1))
                 . strtoupper(substr($part2, 0, 1)) . strtolower(substr($part2, 1));
        }else {
            $ret = $part1 . '_' . $part2;
        }
        return $ret;
    }

    public function setAuthorizationCfg() {
        $aCfg = ['_default'              => 'admin'  //default case
                 ,'cancel'		 => 'read'
                 ,'cancel_partial'	 => 'read'
                 ,'copy_image_to_project'=> 'upload'
                 ,'diff'		 => 'read'
                 ,'draft'		 => 'editing'
                 ,'edit'		 => 'read'
                 ,'get_image_detail'	 => 'read'
                 ,'login'                => 'command'
                 ,'lock'                 => 'read'
                 ,'media'		 => 'read'
                 ,'media_delete'	 => 'delete'
                 ,'media_edit'		 => 'write'
                 ,'media_upload'	 => 'upload'
                 ,'mediadetails'	 => 'read'
                 ,'mediadetails_delete'	 => 'deleteMedia'
                 ,'mediadetails_edit'	 => 'write'
                 ,'mediadetails_upload'	 => 'upload'
                 ,'new_page'		 => 'create'
                 ,'page'		 => 'read'
                 ,'revision'		 => 'write'
                 ,'save'		 => 'write'
                 ,'save_partial'	 => 'write'
                 ,'unlock'               => 'read'
                 ,'user_list'            => 'editing'
                 ,"_none"                => 'command'
                ];
        $this->authCfg = $aCfg;
    }

}
