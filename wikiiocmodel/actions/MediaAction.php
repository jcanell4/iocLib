<?php
/**
 * Description of MediaAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_PLUGIN . "wikiiocmodel/");
require_once WIKI_IOC_MODEL . "projects/defaultProject/datamodel/DokuMediaModel.php";

abstract class MediaAction extends DokuAction
{
    protected $dokuModel;
    protected $persistenceEngine;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->persistenceEngine = $modelManager->getPersistenceEngine();
        $this->dokuModel = new DokuMediaModel($this->persistenceEngine);
    }

   /**
     * És un mètode per sobrescriure. Per defecte no fa res, però la
     * sobrescriptura permet fer assignacions a les variables globals de la
     * wiki a partir dels valors de DokuAction#params.
     */
    abstract protected function initModel();

    protected function startProcess() {
        global $ID, $IMG, $REV, $SRC, $NS, $DEL;

        if (!$this->params[MediaKeys::KEY_ID]) {
            if($this->params[MediaKeys::KEY_FROM_ID]){
                $this->params[MediaKeys::KEY_ID] = $this->params[MediaKeys::KEY_FROM_ID];
            }else if($this->params[MediaKeys::KEY_NS]){
                $this->params[MediaKeys::KEY_ID] = $this->params[MediaKeys::KEY_FROM_ID] = $this->params[MediaKeys::KEY_NS].":*";
            }
        }else{
            $this->params[MediaKeys::KEY_FROM_ID] = $this->params[MediaKeys::KEY_ID];
        }
        $ID = $this->params[MediaKeys::KEY_ID];

        if ($this->params[MediaKeys::KEY_REV]) {
            $REV = $this->params[MediaKeys::KEY_REV];
        }

        if ($this->params[MediaKeys::KEY_IMAGE]){
            $IMG = $this->params[MediaKeys::KEY_IMG] = $this->params[MediaKeys::KEY_IMAGE];
            $SRC = mediaFN($this->params[MediaKeys::KEY_IMAGE]);
        }else if($this->params[MediaKeys::KEY_IMG]){
            $IMG = $this->params[MediaKeys::KEY_IMAGE] = $this->params[MediaKeys::KEY_IMG];
            $SRC = mediaFN($this->params[MediaKeys::KEY_IMAGE]);
        }

        if ($this->params[MediaKeys::KEY_DELETE]){
            $DEL = $this->params[MediaKeys::KEY_DELETE];
            if (!$this->params[MediaKeys::KEY_IMAGE]){
                $IMG = $this->params[MediaKeys::KEY_IMG] = $this->params[MediaKeys::KEY_IMAGE] = $this->params[MediaKeys::KEY_DELETE];
            }
        }else if($this->params[MediaKeys::KEY_MEDIA_DO]
                 && $this->params[MediaKeys::KEY_MEDIA_DO] === MediaKeys::KEY_DELETE
                 && $this->params[MediaKeys::KEY_IMAGE]){
            $DEL = $this->params[MediaKeys::KEY_IMAGE];
        }

        if ($this->params[MediaKeys::KEY_MEDIA_ID] && !$this->params[MediaKeys::KEY_MEDIA_NAME]){
            $this->params[MediaKeys::KEY_MEDIA_NAME] = $this->params[MediaKeys::KEY_MEDIA_ID];
        }elseif($this->params[MediaKeys::KEY_MEDIA_NAME] && !$this->params[MediaKeys::KEY_MEDIA_ID]){
            $this->params[MediaKeys::KEY_MEDIA_ID] = $this->params[MediaKeys::KEY_MEDIA_NAME];
        }

        $this->initModel();

        $NS = $this->params[MediaKeys::KEY_NS] = $this->dokuModel->getNS();
    }

    protected function getModel(){
        return $this->dokuModel;
    }

    function mediaManagerFileList(){
        $content = "";
        //$rev = '';
        $image = cleanID($this->params[MediaKeys::KEY_IMAGE]);

        if (isset($this->params[MediaKeys::KEY_REV])) {
            $rev = $this->params[MediaKeys::KEY_REV];
        }else{
            $jumpto = $image;
        }

        $content .= '<div id="mediamanager__page">' . NL;
        if ($this->params[MediaKeys::KEY_NS] == "") {
            $content .= '<h1>Documents de l\'arrel de documents</h1>';
        } else {
            $content .= '<h1>Documents de ' . $this->params[MediaKeys::KEY_NS] . '</h1>';
        }

        $content .= '<div class="panel filelist ui-resizable">' . NL;
        $content .= '<div class="panelContent">' . NL;

        $do = $this->params[MediaKeys::KEY_MEDIA_DO];     //$do = $AUTH;
        $query = ($this->params[MediaKeys::KEY_QUERY]) ? $this->params[MediaKeys::KEY_QUERY] : "";    //$_REQUEST['q'];

        ob_start();
        if ($do == 'searchlist' || $query) {
            media_searchlist($query, $this->params[MediaKeys::KEY_NS], $do, TRUE, $this->params[MediaKeys::KEY_SORT]);
        } else {
            media_tab_files($this->params[MediaKeys::KEY_NS], $do, $jumpto);
        }
        $content .= ob_get_clean();

        $content .= '</div>' . NL;
        $content .= '</div>' . NL;
        $content .= '</div>' . NL;

        return $content;
    }

    // Esta función duplica la misma en lib/plugins/wikiiocmodel/projects/defaultProject/DokuModelAdapter.php
    protected function runBeforePreProcess(&$content) {
        global $ACT;

        $brun = FALSE;
        // give plugins an opportunity to process the action
        $this->ppEvt = new Doku_Event('ACTION_ACT_PREPROCESS', $ACT);
        ob_start();
        $brun = ($this->ppEvt->advise_before());
        $content = ob_get_clean();

        return $brun;
    }

    // Esta función duplica la misma en lib/plugins/wikiiocmodel/projects/defaultProject/DokuModelAdapter.php
    protected function runAfterPreProcess(&$content) {
        ob_start();
        $this->ppEvt->advise_after();
        $content .= ob_get_clean();
        unset($this->ppEvt);
    }

}
