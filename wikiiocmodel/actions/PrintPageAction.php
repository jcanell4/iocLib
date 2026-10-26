<?php
/**
 * Description of PrintPageAction
 * @author josep
 */
if (!defined('DOKU_INC')) die();

class PrintPageAction extends PageAction{

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        //Indica que la resposta es renderitza i caldrà llançar l'esdeveniment quan calgui
        $this->setRenderer(TRUE);
    }

    protected function responseProcess(){
        $ret = array();
        ob_start();
        include DOKU_TPL_INCDIR.'print.php';
        $ret['html'] = ob_get_clean();
        return $ret;
    }

    protected function runProcess() {
        if (!WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_EXISTS)) {
            throw new PageNotFoundException($this->params[PageKeys::KEY_ID], 'pageNotFound');
        }
        if (!WikiIocInfoManager::getInfo("perm")) {
            throw new InsufficientPermissionToViewPageException($this->params[PageKeys::KEY_ID]);
        }
    }

}
