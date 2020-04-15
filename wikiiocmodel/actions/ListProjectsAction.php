<?php
/**
 * Obtiene la lista de tipos de proyecto, es decir, la lista de directorios de proyectos
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ListProjectsAction extends AbstractWikiAction {

    private $persistenceEngine;
    private $model;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->persistenceEngine = $modelManager->getPersistenceEngine();
        $this->model = new BasicWikiDataModel($this->persistenceEngine);
    }

    /**
     * Retorna un JSON que conté la llista de tipus de projectes vàlids
     */
    public function responseProcess() {
        if ($this->params['list_type'] !== FALSE) {
            $metaDataSubSet = ($this->params[ProjectKeys::KEY_METADATA_SUBSET]) ? $this->params[ProjectKeys::KEY_METADATA_SUBSET] : ProjectKeys::VAL_DEFAULTSUBSET;

            $this->model->init([ProjectKeys::KEY_ID              => $this->params[ProjectKeys::KEY_ID],
                                ProjectKeys::KEY_PROJECT_TYPE    => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                                ProjectKeys::KEY_METADATA_SUBSET => $metaDataSubSet
                              ]);
            $listProjectTypes = $this->model->getListProjectTypes($this->params['list_type']!=="array");
            $aList=[];
            foreach ($listProjectTypes as $pTypes) {
                if(WikiGlobalConfig::getConf("projectname_$pTypes")){
                    $aList[] = ['id' => "$pTypes", 'name' => WikiGlobalConfig::getConf("projectname_$pTypes")];
                }else{
                    $aList[] = ['id' => "$pTypes", 'name' => $pTypes];
                }
            }
            $ret = json_encode($aList);
        }
        return $ret;
    }

}
