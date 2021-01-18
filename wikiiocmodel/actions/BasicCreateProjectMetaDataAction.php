<?php
if (!defined('DOKU_INC')) die();

class BasicCreateProjectMetaDataAction extends ProjectMetadataAction {

    /**
     * Crea una estructura de directorios para el nuevo proyecto (tipo de proyecto)
     * a partir del archivo de configuración configMain.json correspondiente
     */
    public function responseProcess() {
        $model = $this->getModel();
        $modelAttrib = $model->getModelAttributes();
        $id = $modelAttrib[ProjectKeys::KEY_ID];
        $projectType = $modelAttrib[ProjectKeys::KEY_PROJECT_TYPE];

        //sólo se ejecuta si no existe el proyecto
        if (!$model->existProject()) {

            $metaDataValues = $this->getDefaultValues();

            $metaData = [
                ProjectKeys::KEY_ID_RESOURCE => $id,
                ProjectKeys::KEY_PROJECT_TYPE => $projectType,
                ProjectKeys::KEY_PERSISTENCE => $this->persistenceEngine,
                ProjectKeys::KEY_METADATA_SUBSET => $modelAttrib[ProjectKeys::KEY_METADATA_SUBSET],
                ProjectKeys::KEY_FILTER => $this->params[ProjectKeys::KEY_FILTER], // opcional
                ProjectKeys::KEY_METADATA_VALUE => json_encode($metaDataValues)
            ];

            $model->setData($metaData);    //crea la estructura y el contenido en 'mdprojects/'
            $model->createDataDir($id);    //crea el directori del projecte a 'data/pages/'
            $v_conf = $model->getMetaDataAnyAttr("versions");
            if ($v_conf){
                $model->setProjectSystemSubSetAttr("versions", $v_conf, $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
            }

            $ret = $model->getData();      //obtiene la estructura y el contenido del proyecto

            //[TODO: Rafael] La asignación de permisos y shortcuts a las 'personas' del proyecto debería hacerse
            //               en el momento de la Generación y no en la Creación
            //[JOSEP] Ara s'han canviat per tal aquells projectes que no necessitin generació puguin actaulitzar-se sense 
            // necessitat de generar-se.
            if(!$model->getNeedGenerateAction()){
                $params = $model->buildParamsToPersons($ret['projectMetaData'], NULL);
                $model->modifyACLPageAndShortcutToPerson($params);
            }

            $ret['info'] = self::generateInfo("info", WikiIocLangManager::getLang('project_created'), $id);  //añade info para la zona de mensajes
            $ret[ProjectKeys::KEY_ID] = $this->idToRequestId($id);
            $ret[ProjectKeys::KEY_NS] = $id;
            $ret[ProjectKeys::KEY_PROJECT_TYPE] = $projectType;

            //Lee la página shortcuts para enviarla al cliente obligándole a hacer un refresh del tab shortcuts
            $ns_shortcut = WikiGlobalConfig::getConf('userpage_ns','wikiiocmodel')
                         . $_SERVER['REMOTE_USER'] . ":"
                         . WikiGlobalConfig::getConf('shortcut_page_name','wikiiocmodel');
            $data = $model->getDataDocument($ns_shortcut);
            $ret[PageKeys::KEY_HTML_SC] = [PageKeys::KEY_HTML_SC => $data['structure']['html']];
        }
        if (!$ret)
            throw new ProjectExistException($id);
        else
            return $ret;
    }

    protected function getDefaultValues(){
        $metaDataValues = array();
        $metaDataKeys = $this->projectModel->getMetaDataDefKeys();
        if ($metaDataKeys) {
            foreach ($metaDataKeys as $key => $value) {
                if ($value['default'])
                    $metaDataValues[$key] = $value['default'];
            }
        }
        //asigna valores por defecto a algunos campos definidos en configMain.json
        $metaDataValues["responsable"] = $_SERVER['REMOTE_USER'];
        $metaDataValues['autor'] = $_SERVER['REMOTE_USER'];

        return $metaDataValues;
    }
}