<?php
if (!defined("DOKU_INC")) die();

class BasicGetUpdatableProjectAction extends BasicGetProjectAction {

    protected function runAction() {
        $response = parent::runAction();
        
        if (! $this->isUpdatedDate($this->params[ProjectKeys::KEY_METADATA_SUBSET])) {
            $this->getModel()->setViewConfigName("updateView");
        }
        return $response;
    }

    protected function isUpdatedDate($metaDataSubSet) {
        return BasicViewUpdatableProjectAction::stIsUpdatedDate($this, $metaDataSubSet);
    }

}