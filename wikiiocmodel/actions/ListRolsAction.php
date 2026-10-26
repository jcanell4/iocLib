<?php
/**
 * Obtiene la lista de roles
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ListRolsAction extends AbstractWikiAction {

//    private $persistenceEngine;
//    private $model;
//
//    public function init($modelManager=NULL) {
//        parent::init($modelManager);
//        $this->persistenceEngine = $modelManager->getPersistenceEngine();
//        $this->model = new BasicWikiDataModel($this->persistenceEngine);
//    }

    /**
     * Retorna un JSON que conté la llista de rols
     */
    public function responseProcess() {
//        $metaDataSubSet = ($this->params[ProjectKeys::KEY_METADATA_SUBSET]) ? $this->params[ProjectKeys::KEY_METADATA_SUBSET] : ProjectKeys::VAL_DEFAULTSUBSET;
//        $this->model->init([ProjectKeys::KEY_ID              => $this->params[ProjectKeys::KEY_ID],
//                            ProjectKeys::KEY_PROJECT_TYPE    => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
//                            ProjectKeys::KEY_METADATA_SUBSET => $metaDataSubSet
//                          ]);
//        $listRols = $this->model->getListRols();
        $listRols[] = ["name" => "autor"];
        $listRols[] = ["name" => "responsable"];
        $listRols[] = ["name" => "revisor"];
        $listRols[] = ["name" => "validador"];
        $ret = json_encode($listRols);
        return $ret;
    }

}
