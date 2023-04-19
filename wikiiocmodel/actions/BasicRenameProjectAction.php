<?php
/**
 * BasicRenameProjectAction
 * @culpable Rafael
 */
if (!defined("DOKU_INC")) die();

class BasicRenameProjectAction extends ViewProjectAction {

    protected function runAction() {
        $model = $this->getModel();
        $response = $model->getData();

        $persons = $response[ProjectKeys::KEY_PROJECT_METADATA]['autor']['value'].",".$response[ProjectKeys::KEY_PROJECT_METADATA]['responsable']['value'];
        $model->renameProject($this->params[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_NEWNAME], $persons);

        $old_ns = $this->params[ProjectKeys::KEY_ID];
        $old_id = $this->idToRequestId($this->params[ProjectKeys::KEY_ID]);

        $this->params[ProjectKeys::KEY_ID] = $newId = $model->getId();
        $this->setGlobalID($newId);
        parent::setParams($this->params);

        $response = parent::runAction();

        $response[ProjectKeys::KEY_OLD_NS] = $old_ns;
        $response[ProjectKeys::KEY_OLD_ID] = $old_id;
        $response[ProjectKeys::KEY_NS] = $model->getId();
        $response[ProjectKeys::KEY_ID] = $this->idToRequestId($response[ProjectKeys::KEY_NS]);
        $response[ProjectKeys::KEY_GENERATED] = $model->isProjectGenerated();

        return $response;
    }

    public function responseProcess() {
        $response = parent::responseProcess();
        return $response;
    }

    protected function initAction() {
        parent::initAction();
        $this->params[ProjectKeys::KEY_NEWNAME] = preg_replace(array('/\s+/', '/:+/'), "", $this->params[ProjectKeys::KEY_NEWNAME]);

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
        $new_message = $this->generateMessageInfoForSubSetProject($response[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET], WikiIocLangManager::getLang('project_renamed','wikiiocmodel'));
        $response['info'] = self::addInfoToInfo($response['info'], $new_message);
    }

    public function requireResource($lock = FALSE) {
        $this->resourceLocker->init($this->params, TRUE);
        return $this->resourceLocker->requireResource($lock);
    }

    private function setGlobalID($id){
        global $INPUT;
        $INPUT->set("id", $id);
    }
    
}
