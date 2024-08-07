<?php
/**
 * AbstractModelManager: proporciona ModelAdapter y autorizaciones propias
 *                      de un proyecto concreto, o bien, del proyecto por defecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC."lib/lib_ioc/");
if (!defined('WIKI_LIB_IOC_MODEL')) define('WIKI_LIB_IOC_MODEL', DOKU_LIB_IOC."wikiiocmodel/");

abstract class AbstractModelManager {

    private $persistenceEngine;
    private $projectType;
    private $confProjectType;

    public function __construct($projectType=NULL) {
        $this->projectType = $projectType;
        $this->persistenceEngine = new \BasicPersistenceEngine();
    }

    public static function Instance($projectType){
        if (!$projectType) {
            global $plugin_controller;
            $projectType = $plugin_controller->getProjectType();
        }
        $inst = self::createModelManager($projectType);
        return $inst;
    }

    private static function createModelManager($projectType){
        global $plugin_controller;
        $dir = $plugin_controller->getProjectTypeDir($projectType);
        $file = realpath("{$dir}{$projectType}DokuModelManager.php");
        require_once $file;
        $dmm = "{$projectType}DokuModelManager";
        return new $dmm($projectType);
    }

    public function getPersistenceEngine() {
        return $this->persistenceEngine;
    }

    public function getProjectRoleData($id, $projectType=NULL, $rev=NULL, $viewConfigName=ProjectKeys::KEY_VIEW_DEFAULTVIEW, $metadataSubset=ProjectKeys::VAL_DEFAULTSUBSET) {
        $ret = array();
        $class = $this->getProjectType()."ProjectModel";
        $obj = new $class($this->persistenceEngine);
        $obj->init($id, $projectType, $rev, $viewConfigName, $metadataSubset);
        if (is_callable([$obj, "getRoleData"])){
            $ret["roleData"] = $obj->getRoleData();
            $ret["roleProperties"] = $obj->getRoleProperties();
        }
        return $ret;
    }

    public function getProjectType() {
        return $this->projectType;
    }
    
    public function getConfigProjectType() {
        if (!$this->confProjectType)
            $this->confProjectType = WikiGlobalConfig::getConf('projects','wikiiocmodel')['configuration'];
        return $this->confProjectType;
    }

    public function getNotifyModel($type) {
        switch ($type) {
            case 'ajax':      return new TimerNotifyModel($this->getPersistenceEngine());
            case 'websocket': return new WebsocketNotifyModel($this->getPersistenceEngine());
            default:          throw new UnknownTypeParamException($type);
        }
    }

    public abstract function getProjectTypeDir();

    public function getActionInstance($className, $params=NULL, $noInit=FALSE){
        $classPath = $this->getProjectTypeDir()."actions/$className.php";
        if (@file_exists($classPath)) {
            require_once $classPath;
        }else{
            $classPath = WIKI_LIB_IOC_MODEL."actions/$className.php";
            if (@file_exists($classPath)) {
                require_once $classPath;
            }
        }
        if ($params===NULL){
            $instance = new $className;
        }else{
            $instance = new $className($params);
        }
        if(!$noInit){
            $instance->init($this);
        }
        return $instance;
    }
}
