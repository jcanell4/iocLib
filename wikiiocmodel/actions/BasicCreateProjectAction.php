<?php
if (!defined('DOKU_INC')) die();

class BasicCreateProjectAction extends ProjectAction {

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

            $model->createData($metaData); //crea la estructura y el contenido en 'mdprojects/'
            $model->createDataDir($id);    //crea el directori del projecte a 'data/pages/'
            $v_conf = $model->getMetaDataAnyAttr("versions");
            if ($v_conf){
                $model->setProjectSystemSubSetAttr("versions", $v_conf, $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
            }

            $ret = $model->getData();      //obtiene la estructura y el contenido del proyecto

            if (!$model->getNeedGenerateAction()){
                $params = $model->buildParamsToPersons($ret[ProjectKeys::KEY_PROJECT_METADATA], NULL);
                
                //Verificar si existeix la pestanya 'Dreceres' (mirant si existeix el fitxer dreceres.txt)
                $user_shortcut = $params['userpage_ns'].$metaDataValues['autor'].':'.$params['shortcut_name'];
                $shortcutFile = $model->getRawDocument($user_shortcut);

                $model->modifyACLPageAndShortcutToPerson($params);

                //Si no existeix la pestanya dreceres es crea una resposta
                if ($shortcutFile == "") {
                    $ret['ShortcutTabCreate'] = ['id' => "TAB Dreceres",
                                                 'title' => "Dreceres",
                                                 'content' => $model->getRawDocument($user_shortcut),
                                                 'selected' => TRUE];
                }
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
        
        if ($model->hasTemplates()){
            $model->createTemplateDocument($ret);
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