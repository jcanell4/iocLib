<?php
/**
 * Description of HtmlPageAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

class HtmlPageAction extends RenderedPageAction{

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_SHOW;
    }

    protected function startProcess() {
        parent::startProcess();
    }

    protected function runProcess(){
        $actual = WikiGlobalConfig::getConf('datadir')."/".str_replace(":","/",$this->params[PageKeys::KEY_ID]).".txt";
        if (!WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_EXISTS) && !file_exists($actual)) {
            throw new PageNotFoundException($this->params[PageKeys::KEY_ID]);
        }
        if (!WikiIocInfoManager::getInfo("perm")) {
            throw new InsufficientPermissionToViewPageException($this->params[PageKeys::KEY_ID]);
        }
    }

    protected function responseProcess(){
        global $INFO;
        $response = parent::responseProcess();

        $response['structure']['perm'] = $INFO['perm'];
        
        $info = self::generateInfo("info", WikiIocLangManager::getLang('document_loaded'), $this->params[PageKeys::KEY_ID]);
        if (!$response['info']) {
            $response['info'] = $info;
        }else {
            self::addInfoToInfo($response['info'], $info);
        }

        // TODO: Afegir els drafts des del responseprocess del pare?
        $drafts = $this->dokuPageModel->getAllDrafts();
        if (count($drafts)>0) {
            $response['drafts'] = $drafts;
        }

        return $response;
    }
}
