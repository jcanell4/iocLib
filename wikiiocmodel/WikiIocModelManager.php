<?php
/**
 * WikiIocModelManager: permite crear una instancia de AbstractModelManager a quien no hereda
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();
require_once(DOKU_INC . 'lib/lib_ioc/wikiiocmodel/AbstractModelManager.php');

class WikiIocModelManager extends AbstractModelManager {

    public function getProjectDir(){
      throw new IllegalCallExeption("WikiIocModelManager: getProjectDir()");
    }

}
