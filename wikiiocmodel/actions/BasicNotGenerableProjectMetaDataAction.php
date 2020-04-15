<?php
if (!defined("DOKU_INC")) die();

/**
 * Extensió particular que afecta als projectes que no tenen 'Generació de Projecte'
 * Modifica els permisos i arxius de les 'persones' del projecte
 */
class BasicNotGenerableProjectMetaDataAction extends BasicSetProjectMetaDataAction {

    /**
     * Envía los datos del proyecto al ProjectModel y obtiene la estructura y los valores del proyecto
     * @return array con la estructura y los valores del proyecto
     */
    protected function responseProcess() {
        $response = parent::responseProcess();

        $model = $this->getModel();
        if ($response[ProjectKeys::KEY_GENERATED]){
            $ns_continguts = $model->getContentDocumentId($response);
            p_set_metadata($ns_continguts, array('metadataProjectChanged' => time()));
        }else {
            $params = $model->buildParamsToPersons($response['projectMetaData'], $response['old_persons']);
            $model->modifyACLPageAndShortcutToPerson($params);
        }

        return $response;
    }

}
