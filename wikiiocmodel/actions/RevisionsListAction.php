<?php
/**
 * RevisionsListAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

class RevisionsListAction extends HtmlPageAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function runProcess(){
        $actual = WikiGlobalConfig::getConf('datadir')."/".str_replace(":","/",$this->params[PageKeys::KEY_ID])."/continguts.txt";
        if (!WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_EXISTS) && !file_exists($actual)) {
            throw new PageNotFoundException($this->params[PageKeys::KEY_ID]);
        }
        if (!WikiIocInfoManager::getInfo("perm")) {
            throw new InsufficientPermissionToViewPageException($this->params[PageKeys::KEY_ID]);
        }
    }

    protected function responseProcess(){
        return $this->getRevisionList($this->params[PageKeys::KEY_OFFSET]);;
    }

}
