<?php
/**
 * Description of CreatePageAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();
//if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once (DOKU_INC.'inc/common.php');

class CreatePageAction extends SavePageAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_CREATE;
    }

    protected function responseProcess() {

        $response = RenderedPageAction::staticResponseProcess($this);

        if (!$response['info']) {
            $id = str_replace(":", "_", $this->params[PageKeys::KEY_ID]);
            $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('document_created'), $id);
        }

        return $response;
    }

    protected function runProcess() {
        $actual = WikiGlobalConfig::getConf('datadir')."/".str_replace(":","/",$this->params[PageKeys::KEY_ID]).".txt";
        if (WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_EXISTS) || file_exists($actual)) {
            throw new PageAlreadyExistsException($this->params[PageKeys::KEY_ID], 'pageExists');
        }
        parent::runProcess();
        $this->leaveResource(TRUE);
    }

    protected function startProcess() {
        global $ACT, $TEXT;

        parent::startProcess();
        $ACT = PageKeys::DW_ACT_SAVE;

        if (!$this->params[PageKeys::KEY_WIKITEXT]) {
            if ($this->params[PageKeys::KEY_TEMPLATE]) {
                //[TO DO] JOSEP: La forma aquí seria $this->getModel()->getRawTemplate(ID template) i getRawTemplate implementar-lo a PageDokuModel o potser a WikiRenderizableDataModel.
                //$this->params[PageKeys::KEY_WIKITEXT] = $this->getModel()->getPageDataQuery()->getRaw($this->params[PageKeys::KEY_TEMPLATE]);
                if(WikiIocLangManager::isTemplate($this->params[PageKeys::KEY_TEMPLATE])){
                    $this->params[PageKeys::KEY_WIKITEXT] = WikiIocLangManager::getRawTemplate($this->params[PageKeys::KEY_TEMPLATE]);
                }else if(WikiIocLangManager::isKey($this->params[PageKeys::KEY_TEMPLATE])){
                    $this->params[PageKeys::KEY_WIKITEXT] = WikiIocLangManager::getLang($this->params[PageKeys::KEY_TEMPLATE]);
                }else{
                    $this->params[PageKeys::KEY_WIKITEXT] = cleanText(WikiIocLangManager::getLang('createDefaultText'));
                }
            }else {
                $this->params[PageKeys::KEY_WIKITEXT] = cleanText(WikiIocLangManager::getLang('createDefaultText'));
            }
            $this->params[PageKeys::KEY_WIKITEXT] = str_replace(":%nom_d_usuari%", ":".$this->params['user_id'], $this->params[PageKeys::KEY_WIKITEXT]);
            $TEXT = $this->params[PageKeys::KEY_WIKITEXT];
        }
    }

}
