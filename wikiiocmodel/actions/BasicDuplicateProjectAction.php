<?php
/**
 * BasicDuplicateProjectAction: Fa una còpia d'un projecte amb un altre nom o ruta
 * @autor Rafael <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class BasicDuplicateProjectAction extends ProjectMetadataAction {

    protected function runAction() {
        $model = $this->getModel();
        $oldID = $this->params[ProjectKeys::KEY_ID];
        $newID = "{$this->params['new_path']}:{$this->params['new_project']}";

        $response = $model->getData();
        $persons = $response['projectMetaData']['autor']['value'].",".$response['projectMetaData']['responsable']['value'];

        $this->params[ProjectKeys::KEY_ID] = $newID;
        parent::setParams($this->params);

        //Sólo se ejecutará si no existe el proyecto que se desea crear (el duplicado)
        if ($this->getModel()->existProject()) {
            throw new ProjectExistException($this->params[ProjectKeys::KEY_ID]);
        }

        $old = explode(":", $oldID);
        $old_project = array_pop($old);
        $old_path = implode(":", $old);

        $model->duplicateProject($this->params[ProjectKeys::KEY_ID], $old_path, $old_project, $persons);

        $response = $model->getData();
        $response[ProjectKeys::KEY_OLD_NS] = $oldID;
        $response[ProjectKeys::KEY_OLD_ID] = $this->idToRequestId($oldID);
        $response[ProjectKeys::KEY_NS] = $newID;
        $response[ProjectKeys::KEY_ID] = $this->idToRequestId($newID);
        $response[ProjectKeys::KEY_GENERATED] = $model->isProjectGenerated();

        return $response;
    }

    public function responseProcess() {
        $this->initAction();
        $response = $this->runAction();
        $this->postAction($response);
        return $response;
    }

    protected function initAction() {
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
        $new_message = $this->generateMessageInfoForSubSetProject($response[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET], 'project_duplicated');
        $response['info'] = self::addInfoToInfo($response['info'], $new_message);
    }

    public function requireResource($lock = FALSE) {
        $this->resourceLocker->init($this->params, TRUE);
        return $this->resourceLocker->requireResource($lock);
    }

}
