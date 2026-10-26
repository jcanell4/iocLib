<?php
/**
 * RefreshProjectAction
 * @culpable Rafael
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class BasicRefreshProjectAction extends ViewProjectAction implements ResourceLockerInterface {

    protected function runAction() {
        $this->lockStruct = $this->requireResource(TRUE);
        if ($this->lockStruct["state"]!== ResourceLockerInterface::LOCKED){
            throw new FileIsLockedException($this->params[PageKeys::KEY_ID]);
        }
        $response[PageKeys::KEY_CODETYPE] = ProjectKeys::VAL_CODETYPE_OK;
        return $response;
    }

    public function requireResource($lock = FALSE) {
        $this->resourceLocker->init($this->params);
        return $this->resourceLocker->requireResource($lock);
    }
}
