<?php
/**
 * WikiIocModelManager: permite crear una instancia de AbstractModelManager a quien no hereda
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class WikiIocModelManager extends AbstractModelManager {

    public function getProjectTypeDir(){
        throw new IllegalCallExeption("WikiIocModelManager: getProjectTypeDir()");
    }

}
