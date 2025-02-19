<?php
/**
 * DuplicateFolderAction
 * @culpable Rafael
 */
if (!defined("DOKU_INC")) die();

class DuplicateFolderAction extends PageAction {

    protected function runProcess() {
        $model = $this->getModel();
        $model->duplicateFolder($this->params[PageKeys::KEY_OLD_FOLDER_NAME], $this->params[PageKeys::KEY_NEW_FOLDER_NAME]);
        return NULL;
    }

    public function responseProcess() {
        return TRUE;
    }

    protected function startProcess() {
        global $ID, $ACT;
        $ACT = act_clean($this->params[PageKeys::KEY_DO]);
        if (!$this->params[PageKeys::KEY_ID]) {
            $this->params[PageKeys::KEY_ID] = WikiGlobalConfig::getConf(PageKeys::DW_DEFAULT_PAGE);
        }
        $ID = $this->params[PageKeys::KEY_ID];
        $this->dokuPageModel->init($this->params[PageKeys::KEY_ID]);
    }

}
