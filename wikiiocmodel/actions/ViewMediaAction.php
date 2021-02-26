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
        $this->modelAdapter = $this->params['modelAdapter'];
    }

    protected function responseProcess() {
        global $lang, $NS, $ACT, $JSINFO;

        $nou = trigger_event('IOC_WF_INTER', $ACT);
        $response = array(
            "content" => $this->modelAdapter->doMediaManagerPreProcess(),  //[ALERTA Josep] Pot venir amb un fragment de HTML i caldria veure què es fa amb ell.
            "id" => "media",
            "title" => "media",
            "ns" => $NS,
            "imageTitle" => $lang['img_manager'],
            "image" => $this->params[MediaKeys::KEY_IMAGE],
            "fromId" => $this->params[MediaKeys::KEY_FROM_ID],
            "modifyImageLabel" => $lang['img_manager'],
            "closeDialogLabel" => $lang['img_backto']
        );
        $JSINFO = array('id' => "media", 'namespace' => $NS);
        return $response;
    }

    protected function startProcess() {
        parent::startProcess();

        $error = $this->startMediaManager(PageKeys::DW_ACT_MEDIA_MANAGER, $this->params[MediaKeys::KEY_IMAGE], $this->params[MediaKeys::KEY_FROM_ID], $this->params[MediaKeys::KEY_REV]);
        if ($error == 401) {
            throw new HttpErrorCodeException("Access denied", $error);
        } else if ($error == 404) {
            throw new HttpErrorCodeException("Resource " . $this->params[MediaKeys::KEY_IMAGE] . " not found.", $error);
        }
    }

    protected function runProcess() {
    }

    protected function initModel() {
    }

    private function startMediaManager($pdo, $pImage=NULL, $pFromId=NULL, $prev=NULL) {
        global $ID, $AUTH, $vector_action, $IMG, $ERROR, $SRC, $REV;
        $ret = $ERROR = 0;

        $this->params['action'] = $pdo;

        if ($pdo === PageKeys::DW_ACT_MEDIA_MANAGER) {
            $vector_action = $GET["vecdo"] = $this->params['vector_action'] = "media";
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
            if ($AUTH >= AUTH_READ) {
                // check if image exists
                $SRC = mediaFN($pImage);
                if (!file_exists($SRC)) {
                    $ret = $ERROR = 404;
                }
            } else {
                // no auth
                $ret = $ERROR = 401;
            }
        }

        if ($ret === 0) {
            WikiIocInfoManager::loadMediaInfo();
            $this->startUpLang();
            //detect revision
            if ($this->params[MediaKeys::KEY_REV] < 1) {
                $REV = $this->params[MediaKeys::KEY_REV] = (int)WikiIocInfoManager::getInfo("lastmod");
            }else {
                $REV = $this->params[MediaKeys::KEY_REV] = (int)WikiIocInfoManager::getInfo("rev"); //$INFO comes from the DokuWiki core
            }
            $this->triggerStartEvents();
        }
        return $ret;
    }

}
