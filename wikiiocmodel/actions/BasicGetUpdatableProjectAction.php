<?php
if (!defined("DOKU_INC")) die();

class BasicGetUpdatableProjectAction extends BasicGetProjectAction {

    protected function runAction() {
        
        if (! $this->isUpdatedDate($this->params[ProjectKeys::KEY_METADATA_SUBSET])) {
            $this->getModel()->setViewConfigName("updateView");
            $new_message = self::generateInfo("info", "El projecte no està actualitzat", $this->params[ProjectKeys::KEY_ID]);
        }

        $response = parent::runAction();

        if ($new_message)
            $response['info'] = self::addInfoToInfo($response['info'], $new_message);
        
        return $response;
    }

    protected function isUpdatedDate($metaDataSubSet) {
        return BasicViewUpdatableProjectAction::stIsUpdatedDate($this, $metaDataSubSet);
    }

}
