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
    }

    protected function responseProcess() {
        global $MSG, $NS, $INPUT, $JSINFO;

        $mdpp = $this->doMediaDetailsPreProcess();
        if ($mdpp['error']) {
            throw new UnknownMimeTypeException();
        }
        if ($mdpp['newImage']) {
            $image = $mdpp['newImage'];
        }
        $response = array(
            "content" => $mdpp['content'],
            "id" => $image,
            "title" => $image,
            "ns" => $NS,
            "imageTitle" => $image,
            "image" => $image,
            "newImage" => ($mdpp['newImage']) ? TRUE : NULL
        );
        $do = $INPUT->str('mediado');
        if ($do === 'diff') {
            $response["mediado"] = $do;
        }
        if ($MSG[0] && $MSG[0]['lvl'] == 'error') {
            throw new HttpErrorCodeException($MSG[0]['msg'], 404);
        }
        $JSINFO = array('id' => $image, 'namespace' => $NS);

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
     * Omple alguns valors de $this->params
     * Retorna l'ERROR de permisos de la imatge
     */
    private function startMediaDetails($pdo, $pImage) {
        global $ID, $AUTH, $IMG, $ERROR, $SRC, $REV, $INPUT;

        $ret = $ERROR = 0;
        $this->params['action'] = $pdo;
        $ID = $pImage;

        if ($pImage) {
            $IMG = $this->params['image'] = $pImage;
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

        if (!$this->params['ns'] && !$this->params['img']){
            $INPUT->set('img', $IMG);
        }

        if ($ret === 0) {
		     WikiIocInfoManager::loadMediaInfo();
		     //detect revision
		     $REV = $this->params['rev'] = (int)WikiIocInfoManager::getInfo("rev"); //$INFO comes from the DokuWiki core
		     $this->triggerStartEvents();
        }

        return $ret;
    }

}

