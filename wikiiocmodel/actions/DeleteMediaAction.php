<?php
/**
 * Description of DeleteMediaAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();
//require_once DOKU_INC."inc/media.php";  //revisar si cal.

class DeleteMediaAction extends MediaAction{

    private $actionReturn;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess(){
        global $JSINFO, $lang, $conf;

        $this->actionReturn;

        if ($this->actionReturn & DOKU_MEDIA_DELETED) {
            $ret = array(
                "content" => $this->mediaManagerFileList(),      //[ALERTA Josep] Pot venir amb un fragment de HTML i caldria veure què es fa amb ell.
                "id" => "media",
                "title" => "media",
                "ns" => $this->params[MediaKeys::KEY_NS],
                "imageTitle" => noNS($this->params[MediaKeys::KEY_IMAGE]),
                "image" => $this->params[MediaKeys::KEY_IMAGE],
                "fromId" => $this->params[MediaKeys::KEY_FROM_ID],
                "modifyImageLabel" => $lang['img_manager'],
                "closeDialogLabel" => $lang['img_backto'],
                "info" => sprintf($lang['deletesucc'], noNS($this->params[MediaKeys::KEY_IMAGE])),
                "result" => $this->actionReturn
            );
            $JSINFO = array('id' => "media", 'namespace' => $this->params[MediaKeys::KEY_NS]);
        }
        elseif ($this->actionReturn & DOKU_MEDIA_INUSE) {
            if(!$conf['refshow']) {
                $ret =array(
                    "info" => sprintf($lang['mediainuse'], noNS($this->params[MediaKeys::KEY_IMAGE])),
                    "result" => $this->actionReturn
                );
            }
        }
        else {
            $ret =array(
                "info" => sprintf($lang['deletefail'], noNS($this->params[MediaKeys::KEY_IMAGE])),
                "result" => $this->actionReturn
            );
        }
        return $ret;
    }

    protected function runProcess() {
        if (auth_quickaclcheck( getNS( $this->params[MediaKeys::KEY_IMAGE] ) . ":*" )< AUTH_DELETE) {
            throw new HttpErrorCodeException("Access denied", 401);
        }
        if(!$this->dokuModel->exist()){
            throw new HttpErrorCodeException("Resource " . $this->params[MediaKeys::KEY_IMAGE] . " not found.", 404);
        }
        $this->actionReturn = $this->dokuModel->delete();
    }

    protected function initModel() {
        $this->dokuModel->init($this->params[MediaKeys::KEY_IMAGE], $this->params[MediaKeys::KEY_REV], $this->params[MediaKeys::KEY_META], $this->params[PageKeys::KEY_ID], $this->params[MediaKeys::KEY_NS_TARGET]);
    }

    function mediaManagerFileList(){
        global $NS, $IMG, $JUMPTO, $REV, $lang, $fullscreen, $INPUT, $AUTH;
        $fullscreen = TRUE;

        ob_start();

        $rev = '';
        $image = cleanID($INPUT->str('image'));
        if (isset($IMG))    $image = $IMG;
        if (isset($JUMPTO)) $image = $JUMPTO;
        if (isset($REV) && !$JUMPTO) $rev = $REV;

        echo '<div id="mediamanager__page">' . NL;
        if ($NS == "") {
            echo '<h1>Documents de l\'arrel de documents</h1>';
        } else {
            echo '<h1>Documents de ' . $NS . '</h1>';
        }

        echo '<div class="panel filelist ui-resizable">' . NL;
        echo '<div class="panelContent">' . NL;

        $do = $AUTH;
        $query = $_REQUEST['q'];
        if (!$query) $query = '';

        if ($do == 'searchlist' || $query) {
            media_searchlist($query, $NS, $AUTH, TRUE, $_REQUEST['sort']);
        } else {
            media_tab_files($NS, $AUTH, $JUMPTO);
        }
        echo '</div>' . NL;
        echo '</div>' . NL;
        echo '</div>' . NL;

        return ob_get_clean();
    }

}
