<?php
/**
 * RevisionsProjectListAction
 * @culpable rafael
 */
if (!defined("DOKU_INC")) die();

class RevisionsProjectListAction extends ProjectAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function startProcess() {
        if (!WikiIocInfoManager::getInfo("perm")) {
            throw new InsufficientPermissionToViewPageException($this->params[PageKeys::KEY_ID]);
        }

        $this->getModel()->init([ProjectKeys::KEY_ID           => $this->params[ProjectKeys::KEY_ID],
                                 ProjectKeys::KEY_PROJECT_TYPE => $this->params[ProjectKeys::KEY_PROJECT_TYPE]
                               ]);
    }

    protected function runProcess(){
        $response[ProjectKeys::KEY_REV] = $this->getModel()->getProjectRevisionList(0, $this->params[PageKeys::KEY_OFFSET]);
        $response[ProjectKeys::KEY_ID] = $this->idToRequestId($this->params[ProjectKeys::KEY_ID]);
        return $response;
    }

    protected function responseProcess(){
        $this->startProcess();
        $response = $this->runProcess();
        return $response;
    }

}
