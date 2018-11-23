<?php
/**
 * WikiIocModelManager: permite crear una instancia de AbstractModelManager a quien no hereda
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC . "lib/lib_ioc/");
require_once(DOKU_LIB_IOC . "wikiiocmodel/AbstractModelManager.php");

class WikiIocModelManager extends AbstractModelManager {

    public function getProjectTypeDir(){
        throw new IllegalCallExeption("WikiIocModelManager: getProjectTypeDir()");
    }

}
