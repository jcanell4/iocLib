<?php
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_COMMAND')) define('DOKU_COMMAND', DOKU_INC . "lib/plugins/ajaxcommand/");
require_once(DOKU_COMMAND . "defkeys/ProjectKeys.php");

abstract class abstract_project_command_class extends abstract_command_class {

    protected $dataProject;   //guarda los datos del proyecto para verificar la autorización

    public function __construct() {
        parent::__construct();
        $this->types[ProjectKeys::KEY_ID] = self::T_STRING;
        $this->types[ProjectKeys::KEY_DO] = self::T_STRING;
        //Set default values to $params
        $this->setParameters([ProjectKeys::KEY_DO => ProjectKeys::KEY_VIEW,
                              ProjectKeys::KEY_METADATA_SUBSET => ProjectKeys::VAL_DEFAULTSUBSET]);
    }

    public function init( $modelManager = NULL ) {
        parent::init($modelManager);
        $projectMetaDataQuery = $this->getPersistenceEngine()->createProjectMetaDataQuery();
        $id = ($this->params[ProjectKeys::KEY_NS]) ? $this->params[ProjectKeys::KEY_NS] : $this->params[ProjectKeys::KEY_ID];
        $projectTypeDir = ($this->params[ProjectKeys::KEY_PROJECTTYPE_DIR]) ? $this->params[ProjectKeys::KEY_PROJECTTYPE_DIR] : $this->getModelManager()->getProjectTypeDir();
        $parms = [ProjectKeys::KEY_ID => $id,
                  ProjectKeys::KEY_PROJECT_TYPE => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                  ProjectKeys::KEY_PROJECTTYPE_DIR => $projectTypeDir,
                  ProjectKeys::KEY_METADATA_SUBSET => $this->params[ProjectKeys::KEY_METADATA_SUBSET],
                  'extra' => TRUE
                 ];
        $this->dataProject = $projectMetaDataQuery->getDataProject($parms);
    }

    public function getKeyDataProject($key=NULL) {
        return ($key) ? $this->dataProject[$key] : $this->dataProject;
    }

    public function getAuthorizationType() {
        return $this->params[ProjectKeys::KEY_DO] . "Project";
    }

    protected function getDefaultResponse($response, &$responseGenerator) {}

}
