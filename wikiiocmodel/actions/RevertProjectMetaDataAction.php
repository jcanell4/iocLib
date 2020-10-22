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
        $model = $this->getModel();

        //sólo se ejecuta si existe el proyecto
        if ($model->existProject()) {
            $oldPersonsDataProject = $model->getOldPersonsDataProject($id, $this->params[ProjectKeys::KEY_PROJECT_TYPE], $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
            $dataRevision = $model->getDataRevisionProject($this->params[ProjectKeys::KEY_REV]);

            $metaData = [
                ProjectKeys::KEY_ID_RESOURCE => $id,
                ProjectKeys::KEY_PERSISTENCE => $this->persistenceEngine,
                ProjectKeys::KEY_PROJECT_TYPE => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                ProjectKeys::KEY_METADATA_SUBSET => $this->params[ProjectKeys::KEY_METADATA_SUBSET],
                //ProjectKeys::KEY_REV => $this->params[ProjectKeys::KEY_REV],
                //PageKeys::KEY_SUM => "Revertir Projecte: {$this->params[ProjectKeys::KEY_REV]}",
                ProjectKeys::KEY_METADATA_VALUE => json_encode($dataRevision)
            ];

            $model->setData($metaData);
            $response = $model->getData();

            if ($model->isProjectGenerated()) {
                $params = $model->buildParamsToPersons($response['projectMetaData'], $oldPersonsDataProject);
                $model->modifyACLPageAndShortcutToPerson($params);
            }

            //Elimina todos los borradores dado que estamos haciendo una reversión del proyecto
            $model->removeDraft();

            //afegim les revisions del projecte a la resposta
            $response[ProjectKeys::KEY_REV] = $this->projectModel->getProjectRevisionList(0);

            //Revertimos el número de versión en el archivo de sistema del proyecto
            $fieldRevVersion = json_decode($response[ProjectKeys::KEY_REV][$this->params['rev']]['extra'], TRUE);
            $model->setProjectSystemSubSetVersion(key($fieldRevVersion), current($fieldRevVersion), $this->params[ProjectKeys::KEY_METADATA_SUBSET]);

            $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('project_reverted'), $id, -1, $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
            $response[ProjectKeys::KEY_ID] = $this->idToRequestId($id);

            $response['close'] = [ProjectKeys::KEY_ID => $response[ProjectKeys::KEY_ID].ProjectKeys::REVISION_SUFFIX,
                                  'idToShow' => $response[ProjectKeys::KEY_ID]
                                 ];
            $response['reload'] = ['urlBase' => "lib/exe/ioc_ajax.php?",
                                   'params' => [ProjectKeys::KEY_ID => $id,
                                                ProjectKeys::KEY_CALL => ProjectKeys::KEY_PROJECT,
                                                ProjectKeys::KEY_PROJECT_TYPE => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                                                ProjectKeys::KEY_METADATA_SUBSET => $this->params[ProjectKeys::KEY_METADATA_SUBSET]]
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