<?php
/**
 * Description of CancelPartialEditPageAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

class CancelPartialEditPageAction extends CancelEditPageAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function runProcess() {
        // Si es passa keep_draft = true no s'esborra
        if (!$this->params[PageKeys::KEY_KEEP_DRAFT]) {
            $this->getModel()->removeChunkDraft($this->params[PageKeys::KEY_SECTION_ID]);
        }

        $unlock = isset($this->params[PageKeys::KEY_UNLOCK]) ? $this->params[PageKeys::KEY_UNLOCK] : FALSE; // ALERTA[Xavi] Canviat a false, si no sempre s'allibera

        if (count($this->params[PageKeys::KEY_EDITING_CHUNKS])==0 || $unlock) {
            $this->leaveResource(TRUE);
        }

    }

    protected function responseProcess() {
        $response = $this->getModel()->getData();
        $response['structure']['cancel'] = [$this->params[PageKeys::KEY_SECTION_ID]];

        if ($this->params[PageKeys::DISCARD_CHANGES]) {
            $response['structure']['discard_changes_partial'] =$this->params[PageKeys::DISCARD_CHANGES];
        }

        if($this->params[PageKeys::KEY_TO_REQUIRE]){
            // TODO: afegir el 'meta' que correspongui perquè si va al requiring dialog, el content tool es crerà de nou
            $this->addMetaTocResponse($response);
            // TODO: afegir les revisions
            $response[PageKeys::KEY_REVISIONS] = $this->getRevisionList();
        }
        $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('chunk_closed'), $this->params[PageKeys::KEY_ID]);
        return $response;
    }

}
