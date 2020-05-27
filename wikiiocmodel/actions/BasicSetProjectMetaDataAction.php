<?php
/**
 * BasicSetProjectMetaDataAction: Desa els canvis fets al formulari que defineix el projecte
 */
if (!defined("DOKU_INC")) die();

class BasicSetProjectMetaDataAction extends ProjectMetadataAction {

    /**
     * Envía los datos $metaData del proyecto al ProjectModel y obtiene la estructura y los valores del proyecto
     * @return array con la estructura y los valores del proyecto
     */
    protected function responseProcess() {
        $model = $this->getModel();
        $modelAttrib = $model->getModelAttributes();

        //sólo se ejecuta si existe el proyecto
        if ($model->existProject()) {

            $oldPersonsDataProject = $model->getOldPersonsDataProject($modelAttrib[ProjectKeys::KEY_ID], $modelAttrib[ProjectKeys::KEY_PROJECT_TYPE], $modelAttrib[ProjectKeys::KEY_METADATA_SUBSET]);

            $metaDataValues = $this->netejaKeysFormulari($this->params);
            $metaDataValues = $this->donaEstructuraALesDadesPlanes($metaDataValues, $model->getMetaDataAnyAttr());
            $model->validateFields($metaDataValues);//valida les dades i llença excepció si cal
            if (!$model->validaNom($metaDataValues['autor']))
                throw new UnknownUserException($metaDataValues['autor']." (indicat al camp 'autor') ");
            if (!$model->validaNom($metaDataValues['responsable']))
                throw new UnknownUserException($metaDataValues['responsable']." (indicat al camp 'responsable') ");
            if (!empty($metaDataValues['supervisor']) && !$model->validaNom($metaDataValues['supervisor']))
                throw new UnknownUserException($metaDataValues['supervisor']." (indicat al camp 'supervisor') ");
            if (!$model->validaSubSet($modelAttrib[ProjectKeys::KEY_METADATA_SUBSET]))
                throw new UnknownUserException($modelAttrib[ProjectKeys::KEY_METADATA_SUBSET]." (indicat al 'metaDataSubSet') ");

            $metaData = [
                ProjectKeys::KEY_ID_RESOURCE => $modelAttrib[ProjectKeys::KEY_ID],
                ProjectKeys::KEY_PERSISTENCE => $this->persistenceEngine,
                ProjectKeys::KEY_PROJECT_TYPE => $modelAttrib[ProjectKeys::KEY_PROJECT_TYPE],
                ProjectKeys::KEY_METADATA_SUBSET => $modelAttrib[ProjectKeys::KEY_METADATA_SUBSET],
                ProjectKeys::KEY_FILTER => $this->params[ProjectKeys::KEY_FILTER],  //opcional
                ProjectKeys::KEY_METADATA_VALUE => str_replace("\\r\\n", "\\n", json_encode($metaDataValues))
            ];

            $model->setData($metaData);
            $response = $model->getData();  //obtiene la estructura y el contenido del proyecto
            $response[ProjectKeys::KEY_GENERATED] = $model->isProjectGenerated();

            if ($response[ProjectKeys::KEY_GENERATED]) {
                $params = $model->buildParamsToPersons($response['projectMetaData'], $oldPersonsDataProject);
                $model->modifyACLPageAndShortcutToPerson($params);
            }
            if (!$this->params[ProjectKeys::KEY_KEEP_DRAFT]) {
                $model->removeDraft();
            }

            if ($this->params[ProjectKeys::KEY_NO_RESPONSE]) {
                $response[ProjectKeys::KEY_CODETYPE] = ProjectKeys::VAL_CODETYPE_OK;
            }else{
                $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('project_saved'), $modelAttrib[ProjectKeys::KEY_ID]);
                $response[ProjectKeys::KEY_ID] = $this->idToRequestId($modelAttrib[ProjectKeys::KEY_ID]);
            }
        }

        if (!$response) {
            throw new ProjectExistException($modelAttrib[ProjectKeys::KEY_ID]);
        }else {
            $response['old_persons'] = $oldPersonsDataProject;
            //Añadir propiedades/restricciones del configMain para la creación de elementos dentro del proyecto
            parent::addResponseProperties($response);
            return $response;
        }
    }

    private function netejaKeysFormulari($array) {
        $cleanArray = [];
        $excludeKeys = ['id','do','sectok','projectType','ns','submit', 'cancel','close','keep_draft','no_response','extraProject','metaDataSubSet'];
        foreach ($array as $key => $value) {
            if (!in_array($key, $excludeKeys)) {
                $cleanArray[$key] = $value;
            }
        }
        return $cleanArray;
    }

    private function donaEstructuraALesDadesPlanes($array, $metadataConfig){
        $newArray = array();
        if($metadataConfig["mainType"]["typeDef"]){
            $currentType = $metadataConfig["mainType"];
        }else{
            $currentType = $metadataConfig["mainType"];
        }
        foreach ($array as $key => $value) {
            if(strpos($key,"#")!==FALSE){
                $akeys = explode("#", $key);
                $lim = count($akeys)-1;
                $currentArray = &$newArray;
                for($ind=0; $ind<$lim; $ind++){
                    $k = is_numeric($akeys[$ind])?(int)$akeys[$ind]:$akeys[$ind];
                    if(!isset($currentArray[$k])){
                        $currentArray[$k] = array();
                    }
                    $currentArray = &$currentArray[$k];
                }
                $k = is_numeric($akeys[$lim])?(int)$akeys[$lim]:$akeys[$lim];
                $currentArray[$k] = $value;
            }else{
                $newArray[$key] = $value;
            }
        }
        return $newArray;
    }
}
