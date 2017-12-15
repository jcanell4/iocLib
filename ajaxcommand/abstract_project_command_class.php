<?php
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_COMMAND')) define('DOKU_COMMAND', DOKU_INC . "lib/plugins/ajaxcommand/");
require_once(DOKU_COMMAND . "defkeys/ProjectKeys.php");

abstract class abstract_project_command_class extends abstract_command_class {

    protected $dataProject;   //guarda los datos del proyecto para verificar la autorización
    protected $persistenceEngine;

    public function __construct() {
        parent::__construct();
        $this->types[AjaxKeys::KEY_ID] = self::T_STRING;
        $this->types[AjaxKeys::KEY_DO] = self::T_STRING;

        $defaultValues = [AjaxKeys::KEY_DO => ProjectKeys::KEY_EDIT];
        $this->setParameters($defaultValues);
    }

    public function init( $modelManager = NULL ) {
        parent::init($modelManager);
        $this->persistenceEngine = $this->getPersistenceEngine();
        $projectMetaDataQuery = $this->persistenceEngine->createProjectMetaDataQuery();
        $ns = ($this->params[AjaxKeys::KEY_NS]) ? $this->params[AjaxKeys::KEY_NS] : $this->params[AjaxKeys::KEY_ID];
        $this->dataProject = $projectMetaDataQuery->getDataProject($ns, $this->params[ProjectKeys::KEY_PROJECT_TYPE]);
    }

    public function getKeyDataProject($key=NULL) {
        return ($key) ? $this->dataProject[$key] : $this->dataProject;
    }

    public function getAuthorizationType() {
        $dokey = $this->params[AjaxKeys::KEY_DO];
        switch ($dokey) {
            case ProjectKeys::KEY_EDIT:
            case ProjectKeys::KEY_CREATE:
            case ProjectKeys::KEY_GENERATE:
            case ProjectKeys::KEY_SAVE:
                $dokey .= "Project";
                break;
            default:
                $dokey = "admin";
        }
        return $dokey;
    }

}
