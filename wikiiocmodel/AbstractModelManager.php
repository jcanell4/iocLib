<?php
/**
 * AbstractModelManager: proporciona ModelAdapter y autorizaciones propias
 *                      de un proyecto concreto, o bien, del proyecto por defecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . "lib/plugins/");
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_PLUGIN . "wikiiocmodel/");
require_once WIKI_IOC_MODEL . "datamodel/TimerNotifyModel.php";
require_once WIKI_IOC_MODEL . "datamodel/WebsocketNotifyModel.php";
require_once(WIKI_IOC_MODEL . 'persistence/BasicPersistenceEngine.php');

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
            $projectType = $plugin_controller->getCurrentProject();
        }
        $inst = self::createModelManager($projectType);
        return $inst;
    }

    private static function createModelManager($projectType){
        global $plugin_controller;
        $dir = $plugin_controller->getProjectTypeDir($projectType);
        $file = realpath("{$dir}DokuModelManager.php");
        require_once $file;
        return new DokuModelManager($projectType);
    }

    public function getPersistenceEngine() {
        return $this->persistenceEngine;
    }
    
    public function getProjectRoleData($id, $projectType=NULL, $rev=NULL, $viewConfigName="defaultView", $metadataSubset=Projectkeys::VAL_DEFAULTSUBSET) {
        $ret = array();
        $class = $this->getProjectType()."ProjectModel";
        $obj = new $class($this->persistenceEngine);
        $obj->init($id, $projectType, $rev, $viewConfigName, $metadataSubset);
        if(is_callable([$obj, "getRoleData"])){
            $ret = $obj->getRoleData();
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
            $classPath = WIKI_IOC_MODEL."actions/$className.php";
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
