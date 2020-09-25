<?php
/**
 * Description of DokuPageModel
 * @author josep
 */
if (!defined("DOKU_INC")) die();
require_once(DOKU_INC . 'inc/common.php');

class DokuPageModel extends WikiRenderizableDataModel {

    protected $id;
    protected $selected;
    protected $editing;
    protected $rev;
    protected $recoverDraft;
    protected $draftDataQuery;
    protected $lockDataQuery;
    protected $resourceLocker;  //El $resourceLocker se ha trasladado desde los Actions hasta aquí. Cal revisar los Actions

    public function __construct($persistenceEngine) {
        parent::__construct($persistenceEngine);
        //[NOTA: Rafa] Ya Está en AbstractWikiDataModel
        //$this->pageDataQuery = $persistenceEngine->createPageDataQuery();
        $this->draftDataQuery = $persistenceEngine->createDraftDataQuery();
        $this->lockDataQuery = $persistenceEngine->createLockDataQuery();
        $this->resourceLocker = new ResourceLocker($persistenceEngine);

        $this->format = "DW";
    }

    public function init($id, $editing=NULL, $selected=NULL, $rev=NULL) {
        $this->id = $id;
        $this->editing = $editing;
        $this->selected = $selected;
        $this->rev = $rev;
    }

    public function setData($toSet, $forceSave=false) {
        $params = (is_array($toSet)) ? $toSet : array(PageKeys::KEY_WIKITEXT => $toSet);

        //--- ATENCIÓ: Vigilar aquest id ---
        //$params[PageKeys::KEY_ID] = ($this->id) ? $this->id : $params[PageKeys::KEY_ID];
        if (!$params[PageKeys::KEY_ID])
            $params[PageKeys::KEY_ID] = $this->id;

        $this->resourceLocker->init($params);
        //mirar si està bloquejat i si no ho està => excepció
        if ($this->resourceLocker->checklock() === LockDataQuery::UNLOCKED) {
            $this->getPageDataQuery()->save($params[PageKeys::KEY_ID], $params[PageKeys::KEY_WIKITEXT], $params[PageKeys::KEY_SUM], $params[PageKeys::KEY_MINOR], $forceSave);
        }else {
            throw new UnexpectedLockCodeException($params[PageKeys::KEY_ID], 'ResourceLocked');
        }
    }

    public function getData($partial=FALSE) {
        $ret = ($partial) ? $this->getViewRawData() : $this->getViewData();
        return $ret;
    }

    public function getViewData() {
        $ret['structure'] = self::getStructuredDocument($this->getPageDataQuery(), $this->id,
            $this->editing, $this->selected,
            $this->rev);
        //TODO [Josep] Comprovar si això es pot eliminar (ara el draft es recupera a HtmlAction)
        if ($this->draftDataQuery->hasAny($this->id)) {
            $ret['draftType'] = PageKeys::FULL_DRAFT;
            $ret['draft'] = $this->getDraftAsFull();
        }
        return $ret;
    }

    public function getViewRawData() {
        $response['structure'] = self::getStructuredDocument($this->getPageDataQuery(), $this->id,
            $this->editing, $this->selected,
            $this->rev);

        // El content es necessari en si hi ha un draft structurat local o remot, en aquest punt no podem saber si caldrà el local
        $response['content'] = $this->getChunkFromStructure($response['structure'], $this->selected);

        if ($this->draftDataQuery->hasFull($this->id)) {
            // Si exiteix el esborrany complet, el tipus serà FULL_DRAFT
            $response['draftType'] = PageKeys::FULL_DRAFT;

        } else if ($this->isChunkInDraft($this->id, $response['structure'], $this->selected) && $this->recoverDraft === null) {
            // Si no el chunk seleccionat es troba al draft, i no s'ha indicat que s'ha de recuperar el draft el tipus sera PARTIAL_DRAFT
            $response['draftType'] = PageKeys::PARTIAL_DRAFT;
            $response['draft'] = $this->_getChunkFromDraft($this->id, $this->selected);

            // TODO[Xavi] aquesta comprovació no hauria de ser necessaria, mai s'hauria de desar un draft igual al content, i en qualsevol cas la eliminació s'hauria de fer en un altre lloc
            if ($response['draft']['content'] === $response['content']['editing']) {
                $this->draftDataQuery->removeChunk($this->id, $this->selected);
                unset($response['draft']);
                $response['draftType'] = PageKeys::NO_DRAFT;
            }

        } else {
            $response['draftType'] = PageKeys::NO_DRAFT;
        }

        //readonly si bloquejat
        return $response;
    }

    public function getRawData() {
        $id = $this->id;
        $response['locked'] = checklock($id);
        $response['content'] = $this->getPageDataQuery()->getRaw($id, $this->rev);
        if ($this->draftDataQuery->hasAny($id)) {
            $response['draftType'] = PageKeys::FULL_DRAFT;
        }else{
            $response['draftType'] = PageKeys::NO_DRAFT;
        }

        return $response;
    }

    public function getMetaToc() {
        $toc = $this->getPageDataQuery()->getToc($this->id);
        $toc = preg_replace(
            '/(<!-- TOC START -->\s?)(.*\s?)(<div class=.*tocheader.*<\/div>|<h3 class=.*toggle.*<\/h3>)((.*\s)*)(<!-- TOC END -->)/i',
            '$1<div class="dokuwiki">$2$4</div>$6', $toc
        );
        return $toc;
    }

    public function getRevisionList($offset = 0) {
        return $this->getPageDataQuery()->getRevisionList($this->id, $offset);
    }

    public function getDraftFilename() {
        return $this->draftDataQuery->getFileName($this->id);
    }

    public function removePartialDraft() {
        $this->draftDataQuery->removeStructured($this->id);
    }

    public function removeChunkDraft($chunkId) {
        $this->draftDataQuery->removeChunk($this->id, $chunkId);
    }

    public function getChunkFromDraft() {
        return $this->_getChunkFromDraft($this->id, $this->selected);
    }

    public function getFullDraft() {
        $respose = $this->getDraftAsFull();
        return $respose;
    }

    public function hasDraft(){
        return $this->draftDataQuery->hasAny($this->id);
    }

    private function getDraftAsFull() {
        $draft = null;

        // Existe el draft completo?
        if ($this->draftDataQuery->hasFull($this->id)) {
            // Retornamos el draft completo
            $draft = $this->draftDataQuery->getFull($this->id);

            // Si no, Existe el draft parcial?
        } else if ($this->draftDataQuery->hasStructured($this->id)) {
            // Construimos el draft
            $draft = $this->getFullDraftFromPartials();
        }

        return $draft;
    }

    private function getFullDraftFromPartials() {
        $draftContent = '';

        $structuredDraft = $this->draftDataQuery->getStructured($this->id);
        $chunks = self::getAllChunksWithText($this->id, $this->getPageDataQuery())['chunks'];
        $draftContent .= $structuredDraft['pre'] /*. "\n"*/;

        for ($i = 0; $i < count($chunks); $i++) {
            if (array_key_exists($chunks[$i]['header_id'], $structuredDraft['content'])) {
                $draftContent .= $structuredDraft['content'][$chunks[$i]['header_id']];
            } else {
                $draftContent .= $chunks[$i]['text']['editing'];
            }
        }

        $draft['content'] = $draftContent;
        $draft['date'] = $structuredDraft['date'];
        //$draft['date'] = WikiPageSystemManager::extractDateFromRevision(@filemtime($this->draftDataQuery->getStructuredFilename($this->id)));

        return $draft;
    }

    private function getChunkFromStructure($structure, $selected) {
        $chunks = $structure['chunks'];
        foreach ($chunks as $chunk) {
            if ($chunk['header_id'] == $selected) {
                return $chunk['text'];
            }
        }
        return null;
    }

    public function getBaseDataToSend($id, $rev) {
        $date = WikiIocInfoManager::getInfo('meta')['date']['modified'] + 1;
        if ($rev)
            $title_rev = " - Revisió (" . date("d.m.Y h:i:s", $date) . ")";

        return ['id' => str_replace(":", "_", $id),
                'ns' => $id,
                'title' => tpl_pagetitle($id, TRUE) . $title_rev,
                'rev' => $rev
               ];
    }


    private function _getChunkFromDraft($id, $selected) {
        return $this->draftDataQuery->getChunk($id, $selected);
    }

    /**
     * Hi ha un casos en que no hi ha selected, per exemple quan es cancela un document.
     */
    private static function getStructuredDocument($pageDataQuery, $id, $editing=NULL, $selected=NULL, $rev=NULL) {
        if (!$editing && $selected) {
            $editing = [$selected];
        } else if (!$editing) {
            $editing = [];
        }

        $document = self::getBaseDataToSend($id, $rev);
        $document['selected'] = $selected;
        $document['date'] = WikiIocInfoManager::getInfo('meta')['date']['modified'] + 1;

        $html = $pageDataQuery->getHtml($id, $rev);
        $document['html'] = $html;

        $headerIds = self::getHeadersFromHtml($html);
        $chunks = self::getChunks($pageDataQuery, $id, $rev);

        $editingChunks = [];
        $dictionary = [];

        self::getEditingChunks($pageDataQuery, $editingChunks, $dictionary, $chunks, $id, $headerIds, $editing);

        $lastSuf = count($editingChunks) - 1;
        $document['suf'] = $pageDataQuery->getRawSlices($id, $editingChunks[$lastSuf]['start'] . "-" . $editingChunks[$lastSuf]['end'])[2];

        self::addPreToChunks($pageDataQuery, $editingChunks, $id);

        $document['chunks'] = $chunks;
        $document['dictionary'] = $dictionary;
        $document['locked'] = checklock($id);

        return $document;
    }

    private static function getHeadersFromHtml($html) {
        $pattern = '/(?:<h[123] class="sectionedit\d+" id=")(.+?)">/s'; //aquest patró només funciona si s'aplica el scedit
        preg_match_all($pattern, $html, $match);
        return $match[1]; // Conté l'array amb els ids trobats per cada secció
    }

    private static function getEditingChunks($pageDataQuery, &$editingChunks, &$dictionary, &$chunks, $id, $headerIds, $editing) {
        for ($i = 0; $i < count($chunks); $i++) {
            $chunks[$i]['header_id'] = $headerIds[$i];
            // Afegim el text només al seleccionat i els textos en edició
            if (in_array($headerIds[$i], $editing)) {
                $chunks[$i]['text'] = [];
                //TODO[Xavi] compte! s'ha d'agafar sempre el editing per montar els nostres pre i suf!
                $chunks[$i]['text']['editing'] = $pageDataQuery->getRawSlices($id, $chunks[$i]['start'] . "-" . $chunks[$i]['end'])[1];
                $chunks[$i]['text']['changecheck'] = md5($chunks[$i]['text']['editing']);

                $editingChunks[] = &$chunks[$i];

            }
            if ($headerIds[$i]) {
                $dictionary[$headerIds[$i]] = $i;
            }
        }
    }

    private static function getAllChunksWithText($id, $pageDataQuery) {
        $html = $pageDataQuery->getHtml($id);
        $headerIds = self::getHeadersFromHtml($html);
        $chunks = self::getChunks($pageDataQuery, $id);
        $editing = $headerIds;
        $editingChunks = [];
        $dictionary = [];

        self::getEditingChunks($pageDataQuery, $editingChunks, $dictionary, $chunks, $id, $headerIds, $editing);

        return ['chunks' => $editingChunks, 'dictionary' => $dictionary];

    }

    // Hi ha draft pel chunk a editar?
    private function isChunkInDraft($id, $document, $selected = null) {
        if (!$selected) {
            return false;
        }

        $draft = $this->draftDataQuery->getStructured($id)['content'];

        if ($draft) {
            for ($i = 0; $i < count($document['chunks']); $i++) {
                if (array_key_exists($document['chunks'][$i]['header_id'], $draft)
                            && $document['chunks'][$i]['header_id'] == $selected) {
                    // Si el contingut del draft i el propi es igual, l'eliminem
                    if ($document['chunks'][$i]['text'] . ['editing'] == $draft[$selected]) {
                        $this->removeStructuredDraft($id, $selected);
                    } else {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    private static function addPreToChunks($pageDataQuery, &$chunks, $id) {
        $lastPos = 0;

        for ($i = 0; $i < count($chunks); $i++) {
            // El pre de cada chunk va de $lastPos fins al seu start
            $chunks[$i]['text']['pre'] = $pageDataQuery->getRawSlices($id, $lastPos . "-" . $chunks[$i]['start'])[1];

            // el text no forma part del 'pre'
            $lastPos = $chunks[$i]['end'];
        }
    }


    // Només son editables parcialment les seccions de nivell 1, 2 i 3
    private static function getChunks($pageDataQuery, $id, $rev = NULL) {
        $instructions = $pageDataQuery->getInstructions($id, $rev);
        $chunks = self::_getChunks($instructions);

        return $chunks;
    }

    private static function _getSectionInstructionValue($instruction, $type){
        $ret = FALSE;
        if ($instruction[0] === $type){
            $ret = $instruction;
//        }else if($instruction[0]==='plugin'
//                    && $instruction[1][0]==='iocexportl_ioccontainer'
//                    && $instruction[1][1][0]===2){
//            $i=0;
//            while($i<count($instruction[1][1][1]) && $instruction[1][1][1][$i][0]!==$type){
//                $i++;
//            }
//            if($i<count($instruction[1][1][1])){
//                $ret = $instruction[1][1][1][$i];
//            }
        }
        return $ret;
    }

    // Només son editables parcialment les seccions de nivell 1, 2 i 3
    private static function _getChunks($instructions) {
        $sections = [];
        $currentSection = [];
        $lastClosePosition = 0;
        $lastHeaderRead = '';
        $firstSection = true;

        for ($i = 0; $i < count($instructions); $i++) {
            $currentSection['type'] = 'section';

            $instruction = self::_getSectionInstructionValue($instructions[$i], 'header');
            if ($instruction) {
                $lastHeaderRead = $instruction[1][0];
            }

            $instruction = self::_getSectionInstructionValue($instructions[$i], 'section_open');
            if ($instruction && $instruction[1][0] < 4) {
                // Tanquem la secció anterior
                if ($firstSection) {
                    // Ho descartem, el primer element no conté informació
                    $firstSection = false;
                } else {
                    $currentSection['end'] = $instruction[2];
                    $sections[] = $currentSection;
                }

                // Obrim la nova secció
                $currentSection = [];
                $currentSection['title'] = $lastHeaderRead;
                $currentSection['start'] = $instruction[2];
                $currentSection['params']['level'] = $instruction[1][0];
            }

            // Si trobem un tancament de secció actualitzem la ultima posició de tancament
            $instruction = self::_getSectionInstructionValue($instructions[$i], 'section_close');
            if ($instruction) {
                $lastClosePosition = $instruction[2];
            }

        }
        // La última secció es tanca amb la posició final del document
        $currentSection['end'] = $lastClosePosition;
        $sections[] = $currentSection;

        return $sections;
    }

    public function removeFullDraft() {
        $this->draftDataQuery->removeFull($this->id);
    }

    public function replaceContentForChunk(&$structure, $chunkId, $content) {
        $index = $structure['dictionary'][$chunkId];
        $structure['chunks'][$index]['text']['originalContent'] = $structure['chunks'][$index]['text']['editing'];
        $structure['chunks'][$index]['text']['editing'] = $content;
    }

    public function getFullDraftDate() {
        return $this->draftDataQuery->getFullDraftDate($this->id);
    }

    public function getStructuredDraftDate() {
        return $this->draftDataQuery->getStructuredDraftDate($this->id, $this->selected);
    }

    public function getLockState(){
        return $this->lockDataQuery->checklock($this->id);
    }

    public function pageExists() {
        $filename = $this->getPageDataQuery()->getFileName($this->id);
        return file_exists($filename);
    }

    public function getAllDrafts() {
        $drafts = [];
        $hasStructured = $this->hasStructuredDraft();

        if ($hasStructured) {
            $drafts['structured'] = $this->getStructuredDraft();
        }

        $hasFull = $this->hasFullDraft();

        if ($hasFull) {
            $drafts['full'] = $this->getFullDraft();
        }

        // Si no hi ha draft full, o la data del draft estructurat es més recent, s'envia el draft reestructurat
        if ($hasStructured && (!$hasFull || $drafts['full']['date'] < $drafts['structured']['date'])) {
            $drafts['full'] = $this->getFullDraftFromPartials();
        }


        return $drafts;
    }

    private function hasFullDraft(){
        return $this->draftDataQuery->hasFull($this->id);
    }

    private function hasStructuredDraft(){
        return $this->draftDataQuery->hasStructured($this->id);
    }

    private function getStructuredDraft(){
        return $this->draftDataQuery->getStructured($this->id);
    }

    public function removeDraft($draft) {
        switch ($draft['type']) {
            case 'structured':
                if(isset($draft["section_id"])){
                    return $this->removeChunkDraft($draft["section_id"]);
                }else{
                    return $this->removePartialDraft();
                }
                break;

            case 'full': // TODO[Xavi] Processar el esborrany normal també a través d'aquesta classe
                return $this->removeFullDraft();
                break;

            default:
                // error o no draft
                break;
        }
    }

    public function saveDraft($draft) {
        switch ($draft['type']) {
            case 'structured':
                $this->draftDataQuery->generateStructured($draft['content'], $draft['id'], $draft['date']);
                break;

            case 'full': // TODO[Xavi] Processar el esborrany normal també a través d'aquesta classe
                $this->draftDataQuery->saveFullDraft($draft['content'], $draft['id'], $draft['date']);
                break;

            default:
                break;
        }
    }

    public function get_ftpsend_metadata() { // Nom del fitxer per comprovar la data
        $html = '';
        /*
        $ext = ".zip";
        $file = WikiGlobalConfig::getConf('mediadir').'/'. preg_replace('/:/', '/', $this->id .'/'.preg_replace('/:/', '_', $this->id)). $ext;

        $P = "";
        $nP = "";
        $class = "mf_zip";


        $savedtime = $this->projectMetaDataQuery->getProjectSystemStateAttr("ftpsend_timestamp");

        $fileexists = @file_exists($file);
        if ($fileexists){
            $filetime = filemtime($file);
        }

        if ($fileexists && $savedtime === $filetime) {

            $index = $this->projectMetaDataQuery->getProjectSystemStateAttr("ftpsend_index");

            $url = $this->projectMetaDataQuery->getProjectSystemStateAttr("ftpsend_url") . '/' . $this->projectMetaDataQuery->getProjectSystemStateAttr("ftpsend_index");

            $data = date("d/m/Y H:i:s", $filetime);

            $html.= $P.'<span id="ftpsend" style="word-wrap: break-word;">';
            $html.= '<a class="media mediafile '.$class.'" href="'.$url.'" target="_blank">'. $index .'</a> ';
            $html.= '<span style="white-space: nowrap;">'.$data.'</span>';
            $html.= '</span>'.$nP;
        }else{

            $html.= '<span id="ftpsend">';
            $html.= '<p class="media mediafile '.$class.'">No hi ha cap fitxer pujat al FTP</p>';
            $html.= '</span>';
        }
        */
        return $html;
    }

    /**
     * Canvia el nom del directori indicat a tot l'arbre de directoris de data i
     * les referències a l'antic nom de directori dins dels fitxers afectats
     * @param string $ns : ns original del directori
     * @param string $new_name : nou nom pel directori
     */
    public function renameFolder($ns, $new_name) {
        $base_dir = explode(":", $ns);
        $old_name = array_pop($base_dir);
        $ns = implode(":", $base_dir).":$new_name";
        $base_dir = implode("/", $base_dir);

        if (is_dir("$base_dir/$new_name")) {
            throw new Exception("Acció no permesa: el nom del directori ja existeix");
        }else {
            $this->pageDataQuery->renameDirNames($base_dir, $old_name, $new_name);
            $this->pageDataQuery->renameRenderGeneratedFiles($base_dir, $old_name, $new_name, $this->_arrayTerminators(), TRUE);
            $this->pageDataQuery->changeOldPathInRevisionFiles($base_dir, $old_name, $new_name, $this->_arrayTerminators(), TRUE);
            $this->pageDataQuery->addLogEntryInRevisionFiles($ns, $base_dir, $old_name, $new_name);
            $this->pageDataQuery->changeOldPathInContentFiles($base_dir, $old_name, $new_name, $this->_arrayTerminators(), TRUE);
            $this->pageDataQuery->changeOldPathInACLFile($base_dir, $old_name, $new_name);
        }
    }

    /**
     * @return array Llista de terminacions de fitxers que contenen el nom del directori
     */
    private function _arrayTerminators() {
        return ['extension',
                '_htmlindex\.zip',
                '_pdfindex\.pdf',
                '_material_paper\.pdf'
               ];
    }

}
