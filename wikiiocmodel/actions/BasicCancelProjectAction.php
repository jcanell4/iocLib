<?php
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . "lib/plugins/");

class BasicCancelProjectAction extends ViewProjectAction implements ResourceUnlockerInterface {

    protected function runAction() {
        $response = self::sharedRunAction($this);

        if ($this->params[ProjectKeys::KEY_NO_RESPONSE]) {
            $response[ProjectKeys::KEY_CODETYPE] = ProjectKeys::VAL_CODETYPE_OK;
        }else {
            $response = parent::runAction();
        }

        return $response;
    }

    public static function sharedRunAction($self) {
        $lockStruct = $self->leaveResource(TRUE);
        if ($lockStruct['state']) {
            $response['lockInfo'] = $lockStruct['info']['locker'];
            $response['lockInfo']['state'] = $lockStruct['state'];
        }

        if (!$self->params[ProjectKeys::KEY_KEEP_DRAFT]) {
            $self->getModel()->removeDraft();
        }

        return $response;
    }

    protected function postAction(&$response) {
        if ($response[ProjectKeys::KEY_CODETYPE] !== ProjectKeys::VAL_CODETYPE_OK) {
            $new_message = $this->generateMessageInfoForSubSetProject($response[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET], 'project_canceled');
            $response['info'] = self::addInfoToInfo($response['info'], $new_message);
        }
    }

}
