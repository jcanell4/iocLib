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

        $mdpp = $this->doMediaDetailsPreProcess();
        if ($mdpp['error']) {
            throw new UnknownMimeTypeException();
        }else if ($MSG[0] && $MSG[0]['lvl'] == 'error') {
            throw new HttpErrorCodeException($MSG[0]['msg'], 404);
        }

        if (!$JSINFO) {
            $JSINFO = [];
        }


        $image = ($mdpp['newImage']) ? $mdpp['newImage'] : $this->params[MediaKeys::KEY_IMAGE];
        $response = array(
            "content" => $mdpp['content'],
            "id" => $image,
            "title" => $image,
            "ns" => $NS,
            "imageTitle" => $image,
            "image" => $image,
            "newImage" => ($mdpp['newImage']) ? TRUE : NULL,
            'rev' => $this->params[MediaKeys::KEY_REV]
        );
        if ($this->params[MediaKeys::KEY_MEDIA_DO] === MediaKeys::KEY_DIFF) {
            $response[MediaKeys::KEY_MEDIA_DO] = $this->params[MediaKeys::KEY_MEDIA_DO];
        }

        $JSINFO[MediaKeys::KEY_ID]  = $image;
        $JSINFO[MediaKeys::KEY_NAMESPACE]  = $NS;

        return $response;
    }

    protected function startProcess() {
        parent::startProcess();
        $error = $this->startMediaDetails(PageKeys::DW_ACT_MEDIA_DETAILS, $this->params[MediaKeys::KEY_IMAGE], $this->params[MediaKeys::KEY_FROM_ID], $this->params[MediaKeys::KEY_REV]);
        if ($error == 404) {
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
            $IMG = $pImage;
            $AUTH = auth_quickaclcheck($pImage);
            $SRC = mediaFN($pImage);
            if (!file_exists($SRC)) {
                $ret = $ERROR = 404;
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

    // viene de DokuModelAdapter
    public function doMediaDetailsPreProcess() {
        global $ACT;

        $content = "";
        if ($this->runBeforePreprocess($content)) {
            ob_start();
            $ret = $this->mediaDetailsContent();
            $ret['content'] = $content . $ret['content'];
            // check permissions again - the action may have changed
            $ACT = IocCommon::act_permcheck($ACT);
        }
        $this->runAfterPreprocess($ret['content']);
        return $ret;
    }

    // viene de DokuModelAdapter
    // Prints full-screen media details
    function mediaDetailsContent() {
        global $NS, $IMG, $JUMPTO, $REV, $fullscreen, $INPUT, $AUTH;
        $fullscreen = TRUE;
        require_once DOKU_INC . 'lib/exe/mediamanager.php';

        $rev = '';
        $image = cleanID($INPUT->str('image'));
        if (isset($IMG)) {
            $image = $IMG;
        }
        if (isset($JUMPTO)) {
            if ($JUMPTO === false) {
                $ret['error'] = "UnknownMimeType";
                return $ret;
            }elseif ($JUMPTO != $image) {
                //éste es el caso de un nuevo fichero con un nuevo nombre, cuando se hace upload en una página mediadetails
                $ret['newImage'] = $JUMPTO;
                $image = $JUMPTO;
            }
        }
        if (isset($REV) && !$JUMPTO) {
            $rev = $REV;
        }

        $content = "";
        $do = $INPUT->str(MediaKeys::KEY_MEDIA_DO);
        if ($do == 'diff') {
            echo '<div id="panelMedia_' . $image . '" class="panelContent">' . NL;
            media_diff($image, $NS, $AUTH);
            echo '</div>' . NL;
            $content .= ob_get_clean();
            $patrones = array();
            $patrones[0] = '/<form id="mediamanager__btn_restore"/';
            $patrones[1] = '/<form id="mediamanager__btn_delete"/';
            $patrones[2] = '/<form id="mediamanager__btn_update"/';
            $sustituciones = array();
            $sustituciones[0] = '<form id="mediamanager__btn_restore_' . $image . '"';
            $sustituciones[1] = '<form id="mediamanager__btn_delete_' . $image . '"';
            $sustituciones[2] = '<form id="mediamanager__btn_update_' . $image . '"';
            $content = preg_replace($patrones, $sustituciones, $content);
        } else {
            echo '<div id="panelMedia_' . $image . '" class="panelContent">' . NL;
            $meta = new JpegMeta(mediaFN($image, $rev));
            $unknown = ($meta->_type == "unknown"); //tipus d'imatge desconegut: arxius PDF o ZIP
            $size = media_image_preview_size($image, $rev, $meta);
            if ($size) {
                echo '<div style="float:left;width:47%;margin-right:10px;">' . NL;
                media_preview($image, $AUTH, $rev, $meta);
                echo '</div>' . NL;
            }
            echo '<div style="float:left;width:20%;">' . NL;
            echo '<h1>Dades de ' . $image . '</h1>';
            $this->media_link($image, $rev, $meta);
            $this->media_rev($rev);
            media_details($image, $AUTH, $rev, $meta);
            echo '</div>' . NL;

            if ($_REQUEST['tab_details'] && !$unknown) {
                if (!$size) {
                    throw new HttpErrorCodeException("No es poden editar les dades d'aquest element", 400);//JOSEP: Alerta! Excepció incorrecta, cal buscar o crear una execpció adient!
                } else {
                    if ($_REQUEST['tab_details'] == 'edit') {
                        //$this->params['id'] = "form_".$image;
                        echo '<div style="float:right;margin-right:5px;width:29%;">' . NL;
                        echo "<h1>Formulari d'edició de " . $image . '</h1>';
                        media_metaform($image, $AUTH);
                        echo '</div>' . NL;
                    }
                }
            }
            echo '</div>' . NL;
            $content .= ob_get_clean();
            $patrones = array();
            $patrones[0] = '/<form/';
            $patrones[1] = '/style="max-width:+\s+\d+px;"/';
            $sustituciones = array();
            $sustituciones[0] = '<form id="form_' . $image . '"';
            $sustituciones[1] = '<img style="width: 60%;"';
            $content = preg_replace($patrones, $sustituciones, $content);
        }
        $ret['content'] = $content;
        return $ret;
    }

    // viene de DokuModelAdapter
    function media_link($image, $rev='', $meta=false) {
        global $lang;
        $more = array();
        if ($rev) {$more['rev'] = $rev;}
        else {$more['t'] = @filemtime(mediaFN($image));}
        $size = media_image_preview_size($image, $rev, $meta);
        if ($size) {
            $more['w'] = $size[0];
            $more['h'] = $size[1];
            $src = ml($image, $more);
        }else {
            $src = ml($image, $more);
        }
        echo '<dl><dt>Enllaç:</dt><dd>';
        echo '<a href="'.$src.'" target="_blank" title="'.$lang['mediaview'].'">'.$image.'</a>';
        echo '</dd></dl>'.NL;
    }

    // viene de DokuModelAdapter
    function media_rev($rev=NULL) {
        if (!empty($rev) && $rev > 0) {
            echo '<dl><dt>És una revisió:</dt>';
            echo '<dd>'.WikiPageSystemManager::extractDateFromRevision($rev).'</dd></dl>'.NL;
        }
    }

}

