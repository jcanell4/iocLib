<?php
/**
 * Class abstract_admin_command_class: Classe abstracta de la qual hereten els altres commands
 *                                     que no estan lligats a un projecte específic.
 * @author Rafael <rclaver@xtec.cat>
 */
if(!defined('DOKU_INC')) die();

abstract class abstract_admin_command_class extends abstract_command_class {

    // Constructor en el que s'assigna un nou ModelManager a la classe
    public function init($modelManager = NULL) {
        global $plugin_controller;
        if (!$modelManager) {
            $modelManager = AbstractModelManager::Instance(NULL);
        }
        $plugin_controller->setPersistenceEngine($modelManager->getPersistenceEngine());
        $this->setModelManager($modelManager);
    }

    /**
     * Estableix l'adaptador a emprar i l'autorització que li correspon.
     * @param modelManager
     */
    public function setModelManager($modelManager) {
        $this->modelManager = $modelManager;

        if (!$this->getModelAdapter()) {
            $this->modelAdapter = $modelManager->getModelAdapterManager();
        }
        if (!$this->authorization) {
            $this->authorization = $modelManager->getAuthorizationManager($this->getAuthorizationType());
        }
    }

}
