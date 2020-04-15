<?php
/**
 * Description of UploadMediaAction
 * @author josep
 */
if (!defined('DOKU_INC')) die();

class UploadMediaAction extends MediaAction {
    private $actionReturn;
    private $fileName;
    private $warnings = array();

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function startProcess() {
        parent::startProcess();

        // get file and id
        $this->fileName = $this->params[MediaKeys::KEY_MEDIA_ID];
        if (!$this->fileName){
            $this->params[MediaKeys::KEY_MEDIA_NAME] = $this->params[MediaKeys::KEY_MEDIA_ID] = $this->fileName
                                                     = $this->params[MediaKeys::KEY_UPLOAD][MediaKeys::KEY_NAME];
        }

        list($fext,$fmime,$dl) = mimetype($this->params[MediaKeys::KEY_UPLOAD][MediaKeys::KEY_NAME]);
        list($iext,$imime,$dl) = mimetype($this->fileName);
        if ($fext && !$iext){
            // no extension specified in id - read original one
            $this->fileName .= '.'.$fext;
            $imime = $fmime;
        }elseif($fext && $fext != $iext){
            // extension was changed, print warning
            $this->warnings[] = sprintf(WikiIocLangManager::getLang('mediaextchange'),$fext,$iext);
        }

        if (!$this->params[MediaKeys::KEY_IMAGE]){
            if (!$this->params[MediaKeys::KEY_NS_TARGET]){
                $this->params[MediaKeys::KEY_NS_TARGET] = $this->params[MediaKeys::KEY_NS];
            }
            $this->initModel();
        }
    }

    protected function responseProcess(){
        $res = array(
                "content" => $this->mediaManagerFileList(),      //[ALERTA Josep] Pot venir amb un fragment de HTML i caldria veure què es fa amb ell.
                "id" => "media",
                "title" => "media",
                "ns" => $this->params[MediaKeys::KEY_NS],
                "imageTitle" => noNS($this->params[MediaKeys::KEY_IMAGE]),
                "image" => $this->params[MediaKeys::KEY_IMAGE],
                "fromId" => $this->params[MediaKeys::KEY_FROM_ID],
                "modifyImageLabel" => $lang['img_manager'],
                "closeDialogLabel" => $lang['img_backto'],
        );
        $res['warnings'] = $this->warnings;
        $res['resultCode'] = $this->actionReturn;
        return $res;
    }

    protected function runProcess() {
        if ($this->params[MediaKeys::KEY_UPLOAD][MediaKeys::KEY_ERROR]) {
            switch($this->params[MediaKeys::KEY_UPLOAD][MediaKeys::KEY_ERROR]){
                case 1:
                case 2:
                    throw new MaxSizeExcededToUploadMediaException();
                default:
                    throw new FailToUploadMediaException($this->params[MediaKeys::KEY_UPLOAD][MediaKeys::KEY_ERROR]);
            }
        }

        $toSet = array(
            'filePathSource' => $this->params[MediaKeys::KEY_UPLOAD][MediaKeys::KEY_TMP_NAME],
            'overWrite' => $this->params[MediaKeys::KEY_OVERWRITE]
        );
        $this->actionReturn = $this->dokuModel->upLoadData($toSet);
        /* 0 = OK
         *-1 = UNAUTHORIZED
         *-2 = OVER_WRITING_NOT_ALLOWED
         *-3 = OVER_WRITING_UNAUTHORIZED
         *-5 = FAILS
         *-4 = WRONG_PARAMS
         *-6 = BAD_CONTENT
         *-7 = SPAM_CONTENT
         *-8 = XSS_CONTENT
         */
        //if($this->actionReturn) Falten les excepcions!
    }

    protected function initModel() {
        if ($this->params[MediaKeys::KEY_NS_TARGET]){
            $this->dokuModel->initWhitTarget($this->params[MediaKeys::KEY_NS_TARGET], $this->fileName, $this->params[MediaKeys::KEY_REV], $this->params[MediaKeys::KEY_META]);
        }else {
            $this->dokuModel->initWithId($this->params[MediaKeys::KEY_IMAGE], $this->params[MediaKeys::KEY_REV], $this->params[MediaKeys::KEY_META], $this->params[MediaKeys::KEY_FROM_ID]);
        }
    }

}
