<?php
/**
 * ViewMediaDetailsAction
 * @author rafael <rclaver@xtec.cat>
 */
if (!defined('DOKU_INC')) die();

class ViewMediaDetailsAction extends MediaAction {

    protected $modelAdapter;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        if ($modelManager) {
            $this->modelAdapter = $modelManager->getModelAdapterManager();
        }
    }

    protected function responseProcess() {
        global $MSG, $NS, $JSINFO;

        $mdpp = $this->modelAdapter->doMediaDetailsPreProcess();
        if ($mdpp['error']) {
            throw new UnknownMimeTypeException();
        }
        $image = ($mdpp['newImage']) ? $mdpp['newImage'] : $this->params[MediaKeys::KEY_IMAGE];
        $response = array(
            "content" => $mdpp['content'],
            "id" => $image,
            "title" => $image,
            "ns" => $NS,
            "imageTitle" => $image,
            "image" => $image,
            "newImage" => ($mdpp['newImage']) ? TRUE : NULL
        );
        if ($this->params[MediaKeys::KEY_MEDIA_DO] === 'diff') {
            $response[MediaKeys::KEY_MEDIA_DO] = $this->params[MediaKeys::KEY_MEDIA_DO];
        }
        if ($MSG[0] && $MSG[0]['lvl'] == 'error') {
            throw new HttpErrorCodeException($MSG[0]['msg'], 404);
        }
        $JSINFO = array(MediaKeys::KEY_ID => $image, 'namespace' => $NS);

        return $response;
    }

    protected function startProcess() {
        parent::startProcess();

        $error = $this->startMediaDetails(PageKeys::DW_ACT_MEDIA_DETAILS, $this->params[MediaKeys::KEY_IMAGE], $this->params[MediaKeys::KEY_FROM_ID], $this->params[MediaKeys::KEY_REV]);
        if ($error == 401) {
            throw new HttpErrorCodeException("Access denied", $error);
        } else if ($error == 404) {
            throw new HttpErrorCodeException("Resource " . $this->params[MediaKeys::KEY_IMAGE] . " not found.", $error);
        }
    }

    protected function initModel() {}

    protected function runProcess() {}

    /**
     * Retorna l'ERROR de permisos de la imatge
     */
    private function startMediaDetails($pdo, $pImage) {
        global $ID, $AUTH, $IMG, $ERROR, $SRC, $REV;

        $ret = $ERROR = 0;
        $this->params[MediaKeys::KEY_ACTION] = $pdo;
        $ID = $pImage;

        if ($pImage) {
            $IMG = $this->params[MediaKeys::KEY_IMAGE] = $pImage;
            $AUTH = auth_quickaclcheck($pImage);
            if ($AUTH >= AUTH_READ) {
                $SRC = mediaFN($pImage);
                if (!file_exists($SRC)) {
                    $ret = $ERROR = 404;
                }
            } else {
                $ret = $ERROR = 401;
            }
        }

        if ($ret === 0) {
            WikiIocInfoManager::loadMediaInfo();
            //detect revision
            $REV = $this->params[MediaKeys::KEY_REV] = (int)WikiIocInfoManager::getInfo(MediaKeys::KEY_REV);
            $this->triggerStartEvents();
        }

        return $ret;
    }

}

