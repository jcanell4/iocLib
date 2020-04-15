<?php
/**
 * Description of DraftPageAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

//require_once(DOKU_INC . 'inc/common.php');
//require_once(DOKU_INC . 'inc/actions.php');
//require_once(DOKU_INC . 'inc/template.php');

class DraftPageAction extends PageAction {
    private static $infoDuration = 15;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_PREVIEW;
    }

    protected function startProcess() {
        if ($this->params[PageKeys::KEY_DO]===PageKeys::DW_ACT_PREVIEW || $this->params[PageKeys::KEY_DO]===PageKeys::DW_ACT_SAVE) {
            $this->defaultDo = PageKeys::DW_ACT_PREVIEW;
        }else if($this->params[PageKeys::KEY_DO]===PageKeys::DW_ACT_DRAFTDEL || $this->params[PageKeys::KEY_DO]===PageKeys::DW_ACT_REMOVE) {
            $this->defaultDo = PageKeys::DW_ACT_DRAFTDEL;
        }
        parent::startProcess();
    }

    protected function runProcess() {
        global $ACT;

        if (!WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_EXISTS)) {
            throw new PageNotFoundException($this->params[PageKeys::KEY_ID]);
        }

        $ACT = act_permcheck($this->defaultDo);
        if ($ACT == PageKeys::DW_ACT_DENIED) {
            throw new InsufficientPermissionToEditPageException($this->params[PageKeys::KEY_ID]);
        }

        if($this->checklock()== LockDataQuery::LOCKED){
            throw new FileIsLockedException($this->params[PageKeys::KEY_ID]);
        }

        if($this->params[PageKeys::KEY_DO]===PageKeys::DW_ACT_PREVIEW){
            $lockInfo = $this->updateLock()["info"];
            $draft = json_decode($this->params['draft'], true);
            $draft['date'] = $this->params['date'];
            $this->getModel()->saveDraft($draft);

            $this->response[PageKeys::KEY_ID] = str_replace(":", "_", $this->params[PageKeys::KEY_ID]);

            if($draft['type']==="full"){
                $this->response['info'] = self::generateInfo('info', 'Desat esborrany complet', $this->response['id'], self::$infoDuration);
            }else{
                $this->response['info'] = self::generateInfo('info', 'Desat esborrany parcial', $this->response['id'], self::$infoDuration);
            }
            $this->response["lockInfo"] = $lockInfo;
        }
        else if($this->params[PageKeys::KEY_DO]===PageKeys::DW_ACT_DRAFTDEL){
            $this->getModel()->removeDraft($this->params);
            $this->response[PageKeys::KEY_ID] = str_replace(":", "_", $this->params[PageKeys::KEY_ID]);
        }
        else{
            throw new UnexpectedValueException("Unexpected value '".$this->params["do"]."', for parameter 'do'");
        }
    }

    protected function responseProcess() {
        return $this->response;
    }
}
