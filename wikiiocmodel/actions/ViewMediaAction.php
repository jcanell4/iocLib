<?php
/**
 * ViewMediaAction
 * @author rafael <rclaver@xtec.cat>
 */
if (!defined('DOKU_INC')) die();

class ViewMediaAction extends MediaAction {

    protected $modelAdapter;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        if ($modelManager) {
            $this->modelAdapter = $modelManager->getModelAdapterManager();
        }
    }

    protected function responseProcess() {
        global $lang, $NS, $ACT, $JSINFO;

        trigger_event('IOC_WF_INTER', $ACT);
        $response = array(
            "content" => $this->modelAdapter->doMediaManagerPreProcess(),
            "id" => MediaKeys::KEY_MEDIA,
            "title" => MediaKeys::KEY_MEDIA,
            "ns" => $NS,
            "imageTitle" => $lang['img_manager'],
            "image" => $this->params[MediaKeys::KEY_IMAGE],
            "fromId" => $this->params[MediaKeys::KEY_FROM_ID],
            "modifyImageLabel" => $lang['img_manager'],
            "closeDialogLabel" => $lang['img_backto']
        );
        $JSINFO = [MediaKeys::KEY_ID => MediaKeys::KEY_MEDIA,
                   'namespace' => $NS];
        return $response;
    }

    protected function startProcess() {
        parent::startProcess();

        $error = $this->startMediaManager(MediaKeys::KEY_MEDIA, $this->params[MediaKeys::KEY_IMAGE], $this->params[MediaKeys::KEY_FROM_ID], $this->params[MediaKeys::KEY_REV]);
        if ($error == 404) {
            throw new HttpErrorCodeException("Resource " . $this->params[MediaKeys::KEY_IMAGE] . " not found.", $error);
        }
    }

    protected function initModel() {}

    protected function runProcess() {}

    private function startMediaManager($pdo, $pImage=NULL, $pFromId=NULL, $prev=NULL) {
        global $ID, $AUTH, $vector_action, $IMG, $ERROR, $SRC, $REV;
        $ret = $ERROR = 0;

        $this->params[MediaKeys::KEY_ACTION] = $pdo;

        if ($pdo === MediaKeys::KEY_MEDIA) {
            $vector_action = $GET["vecdo"] = $this->params['vector_action'] = MediaKeys::KEY_MEDIA;
        }
        if ($pImage) {
            $IMG = $this->params[MediaKeys::KEY_IMAGE] = $pImage;
        }
        if ($pFromId) {
            $ID = $this->params[MediaKeys::KEY_ID] = $pFromId;
        }
        if ($prev) {
            $REV = $this->params[MediaKeys::KEY_REV] = $prev;
        }
        // check image permissions
        if ($pImage) {
            $AUTH = auth_quickaclcheck($pImage);
            $SRC = mediaFN($pImage);
            if (!file_exists($SRC)) {
                $ret = $ERROR = 404;
            }
        }

        if ($ret === 0) {
            WikiIocInfoManager::loadMediaInfo();
            //detect revision
            if ($this->params[MediaKeys::KEY_REV] < 1) {
                $REV = $this->params[MediaKeys::KEY_REV] = (int)WikiIocInfoManager::getInfo("lastmod");
            }else {
                $REV = $this->params[MediaKeys::KEY_REV] = (int)WikiIocInfoManager::getInfo(MediaKeys::KEY_REV);
            }
            $this->triggerStartEvents();
        }
        return $ret;
    }

}
