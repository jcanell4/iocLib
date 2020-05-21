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
        $response = parent::responseProcess();

        // TODO: afegir el 'info' que correspongui

        // Si no s'ha especificat cap altre missatge mostrem el de carrega
        if (!$response['info']) {
            $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('document_loaded'), $this->params[PageKeys::KEY_ID]);
        }else {
            self::addInfoToInfo($response['info'], self::generateInfo("info", WikiIocLangManager::getLang('document_loaded'), $this->params[PageKeys::KEY_ID]));
        }

        // TODO: Afegir els drafts des del responseprocess del pare?

        $drafts = $this->dokuPageModel->getAllDrafts();

        if (count($drafts)>0) {
            $response['drafts'] = $drafts;
        }

        return $response;
    }
}
