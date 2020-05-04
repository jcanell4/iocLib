<?php
/**
 * Class abstract_project_command_class: Classe abstracta de la qual hereten els altres commands.
 */
if (!defined('DOKU_INC')) die();

abstract class abstract_project_command_class extends abstract_command_class {

    protected $dataProject;   //guarda los datos del proyecto para verificar la autorización

    public function __construct() {
        parent::__construct();
        $this->types[ProjectKeys::KEY_ID] = self::T_STRING;
        $this->types[ProjectKeys::KEY_DO] = self::T_STRING;
        //Set default values to $params
        $this->setParameters([ProjectKeys::KEY_DO => ProjectKeys::KEY_VIEW,
                              ProjectKeys::KEY_METADATA_SUBSET => ProjectKeys::VAL_DEFAULTSUBSET
                            ]);
    }

    public function init( $modelManager = NULL ) {
        parent::init($modelManager);
        if ($this->params[ProjectKeys::KEY_PROJECT_ID]) {
            $id = ($this->params[ProjectKeys::KEY_PROJECT_ID]);
        }else if($this->params[ProjectKeys::KEY_NS]) {
            $id = $this->params[ProjectKeys::KEY_NS];
        }else {
            $id = $this->params[ProjectKeys::KEY_ID];
        }
        $this->dataProject = $this->getModelManager()->getProjectRoleData(
                                                                    $id,
                                                                    $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                                                                    NULL,
                                                                    "",
                                                                    $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
    }

    public function getKeyDataProject($key=NULL) {
        return ($key) ? $this->dataProject[$key] : $this->dataProject;
    }

    public function getAuthorizationType() {
        return $this->params[ProjectKeys::KEY_DO] . "Project";
    }

    protected function getDefaultResponse($response, &$responseGenerator) {}

    protected function _addExtraData(&$projectMetaData) {
        $rol = $this->authorization->getPermission()->getRol();
        $rolOrder = $this->authorization->getPermission()->getRolOrder();
        if ($rol) {
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_ROL] = $rol;
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_ROL."Order"] = $rolOrder;
        }
        if ($projectMetaData[ProjectKeys::KEY_PROJECT_TYPE]) {
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_PROJECT_TYPE] = $projectMetaData[ProjectKeys::KEY_PROJECT_TYPE];
        }else{
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_PROJECT_TYPE] = $this->params[ProjectKeys::KEY_PROJECT_TYPE];
        }
    }

}
