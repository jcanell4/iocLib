<?php
/**
 * Class abstract_project_command_class: Classe abstracta de la qual hereten els altres commands.
 */
if (!defined('DOKU_INC')) die();

abstract class abstract_project_command_class extends abstract_command_class {

    protected $dataProject;   //guarda los datos del proyecto para verificar la autorización
    protected $roleProperties;   //guarda la propiedades relativas a los roles del proyecto para saber el orden

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
        $roles = $this->getModelManager()->getProjectRoleData($id,
                                                                $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                                                                $this->params[ProjectKeys::KEY_REV],
                                                                "",
                                                                $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
        $this->dataProject = $roles["roleData"];
        $this->roleProperties = $roles["roleProperties"];
    }
    
    public function getRoleProperty($role=NULL, $property=NULL){
        if($role){
            if($property){
                $ret = $this->roleProperties[$role][$property];                
            }else{
                $ret = $this->roleProperties[$role];
            }
        }else{
            $ret= $this->roleProperties;
        }
        return $ret;
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
        $rolList = $this->authorization->getPermission()->getRol(true);
        $rolOrder = $this->authorization->getPermission()->getRolOrder();
        $roleList = $this->authorization->getPermission()->getRol(true);
        if ($rol) {
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_ROL] = $rol;
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_ROL."List"] = $rolList;
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_ROL."Order"] = $rolOrder;
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_ROL."List"] = $roleList;
        }
        if ($projectMetaData[ProjectKeys::KEY_PROJECT_TYPE]) {
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_PROJECT_TYPE] = $projectMetaData[ProjectKeys::KEY_PROJECT_TYPE];
        }else{
            $projectMetaData[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_PROJECT_TYPE] = $this->params[ProjectKeys::KEY_PROJECT_TYPE];
        }
    }
    
    protected function postResponse($responseData, &$ajaxCmdResponseGenerator) {
        parent::postResponse($responseData, $ajaxCmdResponseGenerator);
        if ($responseData[AjaxKeys::KEY_ID] && $responseData[RequestParameterKeys::KEY_CODETYPE] !== RequestParameterKeys::VAL_CODETYPE_REMOVE) {
            $value = ($responseData[AjaxKeys::KEY_ACTIVA_UPDATE_BTN] === "1"||$responseData[AjaxKeys::KEY_ACTIVA_UPDATE_BTN] >=1 ) ? "1" : "0";
            $ajaxCmdResponseGenerator->addExtraContentStateResponse($responseData[AjaxKeys::KEY_ID], "updateButton", $value);
        }
    }
}
