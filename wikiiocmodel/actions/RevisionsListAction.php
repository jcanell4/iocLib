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

    protected function responseProcess(){
        return $this->getRevisionList($this->params[PageKeys::KEY_OFFSET]);;
    }

}
