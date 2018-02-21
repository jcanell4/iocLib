<?php
/**
 * AbstractModelManager: proporciona ModelAdapter y autorizaciones propias
 *                      de un proyecto concreto, o bien, del proyecto por defecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_INC . "lib/plugins/wikiiocmodel/");
require_once WIKI_IOC_MODEL . "datamodel/TimerNotifyModel.php";
require_once WIKI_IOC_MODEL . "datamodel/WebsocketNotifyModel.php";
require_once(WIKI_IOC_MODEL . 'persistence/BasicPersistenceEngine.php');

abstract class AbstractModelManager {

    private $persistenceEngine;

    public function __construct() {
        $this->persistenceEngine = new \BasicPersistenceEngine();
    }

    public static function Instance($projectType){
        if (!$projectType) $projectType = "defaultProject";
        $inst = self::createModelManager($projectType);
        return $inst;
    }

    private static function createModelManager($type){
        require_once(WIKI_IOC_MODEL . "projects/$type/DokuModelManager.php");
        return new DokuModelManager();
    }

    public function getNotifyModel($type) {
        switch ($type) {
            case 'ajax':      return new TimerNotifyModel($this->getPersistenceEngine());
            case 'websocket': return new WebsocketNotifyModel($this->getPersistenceEngine());
            default:          throw new UnknownTypeParamException($type);
        }
    }

    public abstract function getProjectDir();

    public function getPersistenceEngine() {
        return $this->persistenceEngine;
    }

    public function getActionInstance($className, $params=NULL){
        $classPath = WIKI_IOC_MODEL."actions/$className.php";
        if (@file_exists($classPath)) {
            require_once $classPath;
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
