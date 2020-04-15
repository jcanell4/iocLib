<?php
/**
 * ProjectMetadataAction: Define los elementos comunes de las Actions de un proyecto
 * @culpable Rafael Claver
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

abstract class ProjectMetadataAction extends AbstractWikiAction {

    protected $persistenceEngine;
    protected $projectModel;
    protected $resourceLocker;

    public function getActionInstance($actionName) {
        $action = parent::getActionInstance($actionName);
        $action->persistenceEngine = $this->persistenceEngine;
        $action->projectModel = $this->projectModel;
        $action->resourceLocker = $this->resourceLocker ;
        return $action;
    }
    
    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->persistenceEngine = $modelManager->getPersistenceEngine();
        $ownProjectModel = $modelManager->getProjectType()."ProjectModel";
        $this->projectModel = new $ownProjectModel($this->persistenceEngine);
        $this->resourceLocker = new ResourceLocker($this->persistenceEngine);
    }

    protected function setParams($params) {
        parent::setParams($params);
        $this->projectModel->init([ProjectKeys::KEY_ID              => $this->params[ProjectKeys::KEY_ID],
                                   ProjectKeys::KEY_PROJECT_TYPE    => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                                   ProjectKeys::KEY_REV             => $this->params[ProjectKeys::KEY_REV],
                                   ProjectKeys::KEY_METADATA_SUBSET => $this->params[ProjectKeys::KEY_METADATA_SUBSET]
                                ]);
    }

    protected function getModel() {
        return $this->projectModel;
    }

    protected function idToRequestId($requestId) {
        return str_replace(":", "_", $requestId);
    }

    //Añadir propiedades/restricciones del configMain para la creación de elementos dentro del proyecto
    protected function addResponseProperties(&$response) {
        $response[ProjectKeys::KEY_CREATE][ProjectKeys::KEY_MD_CT_SUBPROJECTS] = $this->projectModel->getMetaDataComponent($this->params[ProjectKeys::KEY_PROJECT_TYPE], ProjectKeys::KEY_MD_CT_SUBPROJECTS); //valores permitidos para el elemento 'create project': array | true (all) | false (none)
        $response[ProjectKeys::KEY_CREATE][ProjectKeys::KEY_MD_CT_DOCUMENTS] = $this->projectModel->getMetaDataComponent($this->params[ProjectKeys::KEY_PROJECT_TYPE], ProjectKeys::KEY_MD_CT_DOCUMENTS); //valores permitidos para el elemento 'create document': array | true (all) | false (none)
        $response[ProjectKeys::KEY_CREATE][ProjectKeys::KEY_MD_CT_FOLDERS] = $this->projectModel->getMetaDataComponent($this->params[ProjectKeys::KEY_PROJECT_TYPE], ProjectKeys::KEY_MD_CT_FOLDERS); //valores permitidos para el elemento 'create folder': true (all) | false (none)
    }

    protected function preResponseProcess() {
        if (!isset($this->params[ProjectKeys::KEY_REV]) || $this->params[ProjectKeys::KEY_REV]==NULL) {
            if ($this->projectModel->hasDataProject($this->params[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_PROJECT_TYPE], $this->params[ProjectKeys::KEY_METADATA_SUBSET])) {
                //Actualiza el la estructura y datos del archivo de sistema del proyecto
                if (!$this->projectModel->preUpgradeProject($this->params[ProjectKeys::KEY_METADATA_SUBSET])) {
                    throw new Exception ("Error en l'actualització de la versió de l'arxiu de sistema del projecte");
                }

                //colección de versiones guardada en el subset del fichero system del proyecto
                $versions_project = $this->projectModel->getProjectSystemSubSetAttr("versions", $this->params[ProjectKeys::KEY_METADATA_SUBSET]);

                //colección de versiones establecida en el archivo configMain.json (subset correspondiente) del tipo de proyecto
                $versions_config = $this->projectModel->getMetaDataAnyAttr("versions");
                if ($versions_config) {
                    foreach ($versions_config as $key => $value) {
                        $type = $key;
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                $this->_processVersionChange($v, $versions_project[$key][$k], $versions_project, $type, $k);
                            }
                        }else {
                            $this->_processVersionChange($value, $versions_project[$key], $versions_project, $type);
                        }
                    }
                }
            }
        }
    }
    /**
     * Preparación de parámetros y datos para ser enviados al proceso Upgrader
     * @param int $ver_config  : valor de versión del elemento tratado (del archivo de configuración del tipo de proyecto)
     * @param int $ver_project : valor de versión del elemento tratado (del archivo de sistema del proyecto)
     * @param array $versions_project : array completo del elemento "versions" del archivo de sistema del proyecto
     * @param string $type : tipo de elemento a tratar: "fields", "templates", ...
     * @param string $key : elemento específico del tipo a tratar: por ejemplo, nombre de la plantilla concreta del grupo "templates"
     */
    private function _processVersionChange($ver_config, $ver_project, &$versions_project, $type, $key=NULL) {
        if ($ver_config == NULL) $ver_config = 0;
        if ($ver_project == NULL) $ver_project = 0;

        if ($ver_project > $ver_config) {
            throw new Exception ("La versió de tipus $type del projecte és major que la versió corresponent definida al tipus de projecte: $ver_project > $ver_config");
        }

        if ($ver_project !== $ver_config) {
            $upgader = new UpgradeManager($this->projectModel, $this->params[ProjectKeys::KEY_PROJECT_TYPE], $this->params[ProjectKeys::KEY_METADATA_SUBSET], $ver_project, $ver_config, $type);
            $new_ver = $upgader->process($ver_project, $ver_config, $type, $key);
            if ($key) {
                $versions_project[$type][$key] = $new_ver;
            }else {
                $versions_project[$type] = $new_ver;
            }
            $this->projectModel->setProjectSystemSubSetAttr("versions", $versions_project, $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
            if ($new_ver < $ver_config){
                throw new Exception("Error en l'actualització completa de la versió del projecte.");
            }
        }
    }

    protected function postResponseProcess(&$response) {
        if ($this->params[ProjectKeys::KEY_METADATA_SUBSET] && $this->params[ProjectKeys::KEY_METADATA_SUBSET]!=="undefined" && $this->params[ProjectKeys::KEY_METADATA_SUBSET] !== ProjectKeys::VAL_DEFAULTSUBSET) {
            //$response[ProjectKeys::KEY_ID] = $this->projectModel->addSubSetSufix($response[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
            $response[ProjectKeys::KEY_PROJECT_EXTRADATA][ProjectKeys::KEY_METADATA_SUBSET] = $this->params[ProjectKeys::KEY_METADATA_SUBSET];
            $response['isSubSet'] = TRUE;
        }
        $response[ProjectKeys::KEY_GENERATED] = $this->getModel()->isProjectGenerated();
    }

    public function generateMessageInfoForSubSetProject($id, $subSet, $message) {
        if ($subSet !== "undefined" && $subSet !== ProjectKeys::VAL_DEFAULTSUBSET) {
            $addmessage = " (subconjunt $subSet).";
        }else{
            $addmessage = "";
        }
        $new_message = self::generateInfo("info", WikiIocLangManager::getLang($message), $id);
        $new_message['message'] .= $addmessage;
        return $new_message;
    }

    protected function addNotificationsMetaToResponse(&$response, $ns=NULL, $rev=NULL, $list=NULL) {
        if (!isset($response['meta'])) {
            $response['meta'] = array();
        }
        $ns = isset($response['ns']) ? $response['ns'] : $this->params['id'];
        $rev = $this->params['rev'];

        $list = $this->involvedUserList($response);
        parent::addNotificationsMetaToResponse($response, $ns, $rev, $list);
    }

    protected function involvedUserList($response){
        $list = array();

        $list []=['username' => $response["projectMetaData"]["responsable"]["value"], 'name' => ""];
        if($response["projectMetaData"]["responsable"]["value"]!=$response["projectMetaData"]["autor"]["value"]){
            $list []=['username' => $response["projectMetaData"]["autor"]["value"], 'name' => ""];
        }
        return $list;
    }
}