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
        $plugin_list = $plugin_controller->getList('action');

        //busca el tipo de proyecto solicitado en todos los directorios de plugins del tipo action
        foreach ($plugin_list as $plugin) {
            $file = realpath(DOKU_INC."lib/plugins/$plugin/projects/$projectType/DokuModelManager.php");
            if (file_exists($file)) {
                require_once($file);
                return new DokuModelManager($projectType);
            }
        }
        throw new UnknownPojectTypeException();
    }

    public function getPersistenceEngine() {
        return $this->persistenceEngine;
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

    public function getActionInstance($className, $params=NULL){
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
        $instance->init($this);
        return $instance;
    }
}
