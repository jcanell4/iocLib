<?php
/**
 * AdminAction: Define los elementos comunes de las Actions que no estan sujetas a un proyecto específico
 * @culpable Rafael
 */
if (!defined("DOKU_INC")) die();

abstract class AdminAction extends AbstractWikiAction {

    protected $persistenceEngine;
    protected $adminModel;
    
    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->persistenceEngine = $modelManager->getPersistenceEngine();
        $ownProjectModel = "AdminModel";
        $this->adminModel = new $ownProjectModel($this->persistenceEngine);
    }

    protected function setParams($params) {
        parent::setParams($params);
        $this->adminModel->init($this->params[ProjectKeys::KEY_ID]);
    }

    public function getActionInstance($actionName, $params=NULL, $noInit=TRUE){
        $action = parent::getActionInstance($actionName, $params, $noInit);
        $action->persistenceEngine = $this->persistenceEngine;
        $action->adminModel = $this->adminModel;
        return $action;
    }

    protected function getModel() {
        return $this->adminModel;
    }

    protected function postResponseProcess(&$response) {
    }

}
