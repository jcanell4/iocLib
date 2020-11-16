<?php
/**
 * Converteix la revisió en el projecte actual (reverteix el el projecte a una versió anterior)
 * @author Rafael <rclaver@xtec.cat>
 */
if (!defined('DOKU_INC')) die();

class RevertProjectMetaDataAction extends ProjectMetadataAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function startProcess() {
        $this->getModel()->init([ProjectKeys::KEY_ID              => $this->params[ProjectKeys::KEY_ID],
                                 ProjectKeys::KEY_PROJECT_TYPE    => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                                 ProjectKeys::KEY_REV             => $this->params[ProjectKeys::KEY_REV],
                                 ProjectKeys::KEY_METADATA_SUBSET => $this->params[ProjectKeys::KEY_METADATA_SUBSET]
                               ]);
    }

    /**
     * Envía los datos de la revisión al projectModel para sustituir al proyecto actual
     * @return array con la estructura y los valores del proyecto (la revisión se habrá convertido en el proyecto actual)
     */
    private function localRunProcess() {
        $id = $this->params[ProjectKeys::KEY_ID];
        $projectType = $this->params[ProjectKeys::KEY_PROJECT_TYPE];
        $metaDataSubSet = $this->params[ProjectKeys::KEY_METADATA_SUBSET];
        $rev = $this->params[ProjectKeys::KEY_REV];
        $model = $this->getModel();

        //sólo se ejecuta si existe el proyecto
        if ($model->existProject()) {
            $oldPersonsDataProject = $model->getOldPersonsDataProject($id, $projectType, $metaDataSubSet);
            $dataRevision = $model->getDataRevisionProject($rev); //Datos del proyecto correspondientes a la revisión tratada
            $contentDataRev = json_encode($dataRevision);

            $response = $model->getData(); //Contiene las estructuras de projectMetaData y projectViewData del configMain actual
            if ($model->isProjectGenerated()) {
                $params = $model->buildParamsToPersons($response['projectMetaData'], $oldPersonsDataProject);
                $model->modifyACLPageAndShortcutToPerson($params);
            }
            //Elimina todos los borradores dado que estamos haciendo una reversión del proyecto
            $model->removeDraft();

            //afegim les revisions del projecte a la resposta
            $response[ProjectKeys::KEY_REV] = $this->projectModel->getProjectRevisionList(0);

            //Revertimos el número de versión en el archivo de sistema del proyecto
            $fieldRevVersion = json_decode($response[ProjectKeys::KEY_REV][$rev]['extra'], TRUE);
            if ($fieldRevVersion)
                $model->setProjectSystemSubSetVersion(key($fieldRevVersion), current($fieldRevVersion), $metaDataSubSet);

            //Si este proyecto necesita actualizar el atributo 'updatedDate', lo actualiza con la fecha de la revisión
            if ($model->hasTypeConfigFile($projectType, $metaDataSubSet)) {
                $model->setProjectSystemSubSetAttr("updatedDate", $rev, $metaDataSubSet);
            }

            //Guardar los datos del proyecto correspondientes a la revisión tratada ($contentDataRev) en meta.mdpr
            $summary = "Retorn a la versió " . (($fieldRevVersion) ? current($fieldRevVersion) : $rev);
            $extra = ($fieldRevVersion) ? json_encode($fieldRevVersion) : NULL;
            $model->setDataReversionProject($contentDataRev, $metaDataSubSet, $summary, $extra, FALSE);

            $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('project_reverted'), $id, -1, $metaDataSubSet);
            $response[ProjectKeys::KEY_ID] = $this->idToRequestId($id);

            $response['close'] = [ProjectKeys::KEY_ID => $response[ProjectKeys::KEY_ID].ProjectKeys::REVISION_SUFFIX,
                                  'idToShow' => $response[ProjectKeys::KEY_ID]
                                 ];
            $response['reload'] = ['urlBase' => "lib/exe/ioc_ajax.php?",
                                   'params' => [ProjectKeys::KEY_ID => $id,
                                                ProjectKeys::KEY_CALL => ProjectKeys::KEY_PROJECT,
                                                ProjectKeys::KEY_PROJECT_TYPE => $projectType,
                                                ProjectKeys::KEY_METADATA_SUBSET => $metaDataSubSet]
                                  ];
        }

        if (!$response)
            throw new ProjectExistException($id);

        return $response;
    }

    protected function responseProcess() {
        $this->startProcess();
        $ret = $this->localRunProcess();
        return $ret;
    }

}