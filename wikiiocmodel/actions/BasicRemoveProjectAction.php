<?php
/**
 * BasicRemoveProjectAction
 * @culpable Rafael
 */
if (!defined("DOKU_INC")) die();

class BasicRemoveProjectAction extends BasicViewProjectAction {

    protected function runAction() {
        $model = $this->getModel();
        $response = $model->getData();

        $persons = $response[ProjectKeys::KEY_PROJECT_METADATA]['autor']['value'].",".$response[ProjectKeys::KEY_PROJECT_METADATA]['responsable']['value'];
        $model->removeProject($this->params[ProjectKeys::KEY_ID], $persons);

        //Lee la página shortcuts para enviarla al cliente obligándole a hacer un refresh del tab shortcuts
        $ns_shortcut = WikiGlobalConfig::getConf('userpage_ns','wikiiocmodel')
                     . $this->params[PageKeys::KEY_USER_ID] . ":"
                     . WikiGlobalConfig::getConf('shortcut_page_name','wikiiocmodel');
        $data = $model->getDataDocument($ns_shortcut);

        $response = [ProjectKeys::KEY_ID => $this->idToRequestId($this->params[ProjectKeys::KEY_ID]),
                     ProjectKeys::KEY_CODETYPE => ProjectKeys::VAL_CODETYPE_REMOVE,
                     PageKeys::KEY_HTML_SC => [PageKeys::KEY_HTML_SC => $data['structure']['html']]
                    ];

        return $response;
    }

    public function responseProcess() {
        $response = parent::responseProcess();
        return $response;
    }

    protected function initAction() {
        parent::initAction();

        $this->lockStruct = $this->requireResource(TRUE);
        if ($this->lockStruct["state"]!== ResourceLockerInterface::LOCKED){
            throw new FileIsLockedException($this->params[PageKeys::KEY_ID]);
        }

        if ($this->resourceLocker->isLockedChild($this->params[PageKeys::KEY_ID])) {
            $this->resourceLocker->leaveResource(TRUE);
            throw new FileIsLockedException($this->params[PageKeys::KEY_ID]);
        }
    }

    protected function postAction(&$response) {
        $this->resourceLocker->leaveResource(TRUE);
        $new_message = $this->generateMessageInfoForSubSetProject($response[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET], 'project_removed');
        $response['info'] = self::addInfoToInfo($response['info'], $new_message);
    }

    public function requireResource($lock = FALSE) {
        $this->resourceLocker->init($this->params, TRUE);
        return $this->resourceLocker->requireResource($lock);
    }
}
