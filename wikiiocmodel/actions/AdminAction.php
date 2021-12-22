<?php
/**
 * AdminAction: Define los elementos comunes de las Actions que no estan sujetas a un proyecto específico
 * @culpable Rafael
 */
if (!defined("DOKU_INC")) die();

abstract class AdminAction extends AbstractWikiAction {
    
   /*JOSEP: Jo definiria dos tipus d'Actions diferents:
    *  1) Action per construir el formulari a partir d'un parametre que identifiqui l'acció. Podria ser un AdminFormAction o un UtilFormAction o ...
    *  2) Action específic per a cada acció. En concret ara necessitem l'action SelectProjectsAction encarregat de gestionar la cerca i retornar la llista.
    */

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

}
