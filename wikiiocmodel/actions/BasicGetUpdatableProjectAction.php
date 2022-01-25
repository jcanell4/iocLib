<?php
if (!defined("DOKU_INC")) die();

class BasicGetUpdatableProjectAction extends BasicGetProjectAction {

    protected function runAction() {
        
        $estat = $this->isUpdatedDate($this->params[ProjectKeys::KEY_METADATA_SUBSET]);
        if ($estat !== BasicViewUpdatableProjectAction::IS_UPDATED) {
            if($this->getModel()->getViewConfigKey()===ProjectKeys::KEY_VIEW_DEFAULTVIEW){
                $this->getModel()->setViewConfigKey(ProjectKeys::KEY_VIEW_UPDATEVIEW);
            }
            if ($estat === BasicViewUpdatableProjectAction::NO_IS_UPDATED)
                $new_message = self::generateInfo("info", "El projecte no està actualitzat", $this->params[ProjectKeys::KEY_ID]);
            else
                $new_message = self::generateInfo("info", "El projecte es troba fora de període", $this->params[ProjectKeys::KEY_ID]);
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
