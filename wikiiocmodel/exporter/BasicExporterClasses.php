<?php
/**
 * BasicExporterClasses: clases de procesos, establecidas en el fichero de configuración,
 *                       correspondientes a los tipos de datos del proyecto
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', realpath(DOKU_INC."lib/plugins/"));
if (!defined('EXPORT_TMP')) define('EXPORT_TMP', DOKU_PLUGIN."tmp/latex/");
require_once DOKU_INC."inc/parserutils.php";
require_once DOKU_INC."inc/io.php";
require_once DOKU_INC."inc/pageutils.php";
require_once DOKU_INC."lib/plugins/iocexportl/lib/renderlib.php";

abstract class AbstractRenderer {
    protected $factory;
    protected $cfgExport;
    protected $extra_data;
    protected $mode;
    protected $filetype;
    protected $styletype;

    public function __construct($factory, $cfgExport=NULL) {
        $this->factory = $factory;
        $this->mode = $factory->getMode();
        $this->filetype = $factory->getFileType();
        if ($cfgExport) {
            $this->cfgExport = $cfgExport;
        }else {
            $this->cfgExport = new cfgExporter();
        }
    }

    public function getTocs() {
        return $this->cfgExport->tocs;
    }

    public function init($extra, $styletype=NULL) {
        $this->extra_data = $extra;
        $this->styletype = $styletype;
        $this->cfgExport->styletype = $styletype;
    }

    public function loadTemplateFile($file) {
        $tmplt = @file_get_contents("{$this->cfgExport->rendererPath}/$file");
        if ($tmplt == FALSE)
            throw new Exception("Error en la lectura de l'arxiu de plantilla: $file");
        return $tmplt;
    }

    public function isEmptyArray($param) {
        $vacio = TRUE;
        foreach ($param as $value) {
            $vacio &= (is_array($value)) ? $this->isEmptyArray($value) : empty($value);
        }
        return $vacio;
    }
    
    public function setStyleTypes($types=""){
        if (is_string($types)) {
            $atypes = preg_split('/(\s*,\s*)*,+(\s*,\s*)*/', trim(str_replace("\t", "    ", $types)));
        }elseif(is_array($types)) {
            $atypes = $types;
        }else {
            $atypes = [];
        }
        $this->styletype = $atypes;
    }
}

class cfgExporter {
    public $id;
    public $aLang = array(); //cadenes traduïdes
    public $lang = 'ca';     //idioma amb el que es treballa
    public $langDir;         //directori amb cadenes traduïdes
    public $gif_images = array();
    public $graphviz_images = array();
    public $latex_images = array();
    public $media_files = array();
    public $export_html = TRUE;
    public $permissionToExport = TRUE;
    public $tmp_dir;
    public $rendererPath;
    public $toc = NULL;
    public $tocs = array();
    public $styletype = NULL;

    public function __construct() {
        $this->tmp_dir = realpath(EXPORT_TMP)."/".rand();
    }
}

abstract class renderComposite extends AbstractRenderer {
    protected $typedef = array();
    protected $renderdef = array();
    /**
     * @param array $typedef : tipo (objeto en configMain.json) correspondiente al campo actual en $data
     * @param array $renderdef : tipo (objeto en configRender.json) correspondiente al campo actual en $data
     */
    public function __construct($factory, $typedef, $renderdef, $cfgExport=NULL) {
        parent::__construct($factory, $cfgExport);
        $this->typedef = $typedef;
        $this->renderdef = $renderdef;
    }

    public function createRender($typedef=NULL, $renderdef=NULL) {
        return $this->factory->createRender($typedef, $renderdef, $this->cfgExport);
    }
    public function getTypesDefinition($key = NULL) {
        return $this->factory->getTypesDefinition($key);
    }
    public function getTypesRender($key = NULL) {
        return $this->factory->getTypesRender($key);
    }
    public function getTypeDef($key = NULL) {
        return ($key === NULL) ? $this->typedef : $this->typedef[$key];
    }
    public function getRenderDef($key = NULL) {
        return ($key === NULL) ? $this->renderdef : $this->renderdef[$key];
    }
    public function getTypedefKeyField($field) { //@return array : objeto key solicitado (del configMain.json)
        $ret = $this->getTypeDef('keys')[$field];
        while ($typeDef = $this->getTypesDefinition($ret["type"])) {
            $ret = array_merge($ret, $typeDef);
        }
        return $ret;
    }
    public function getRenderKeyField($field) { //@return array : objeto key solicitado (del configRender.json)
        return $this->getRenderDef('keys')[$field];
    }
}

class BasicRenderObject extends renderComposite {

    private static $deepLevel=0;
    protected $data = array();

    /**
     * @param array $data : array correspondiente al campo actual del archivo de datos del proyecto
     *                      (o array con todos los campos del proyecto)
     * @return datos renderizados
     */
    public function process($data) {
        self::$deepLevel++;
        $this->data = $data;
        $campos = $this->getRenderFields();
        foreach ($campos as $keyField) {
            $typedefKeyField = $this->getTypedefKeyField($keyField);
            $renderKeyField = $this->getRenderKeyField($keyField);
            $render = $this->createRender($typedefKeyField, $renderKeyField);

            $dataField = $this->getDataField($keyField);
            $render->init($keyField, $renderKeyField['render']['styletype']);

            $this->_createSessionStyle($renderKeyField['render']);
            $arrayDeDatosParaLaPlantilla[$keyField] = $render->process($dataField);
            $this->_destroySessionStyle();
        }
        $extres = $this->getRenderExtraFields();
        if ($extres) {
            foreach ($extres as $item) {
                if ($item["valueType"] == "page" ){
                    $typedefKeyField = ["type" => "string"];
                    $renderKeyField = $this->getRenderKeyField($item["name"]);
                    $render = $this->createRender($typedefKeyField, $renderKeyField);

                    $dataField = $item["value"]; //$this->factory->getProjectModel()->getRawProjectDocument($item["value"]);
                    $render->init($item["name"], $renderKeyField['render']['styletype']);

                    $this->_createSessionStyle($renderKeyField['render']);
                    $arrayDeDatosParaLaPlantilla[$item["name"]] = $render->process($dataField, $item["name"]);
                    $this->_destroySessionStyle();
                }
                else if ($item["valueType"] == "field") {
                    $typedefKeyField = $this->getTypedefKeyField($item["value"]);
                    $renderKeyField = $this->getRenderKeyField($item["name"]);
                    $render = $this->createRender($typedefKeyField, $renderKeyField);

                    $dataField = $this->getDataField($item["value"]);
                    $render->init($item["name"], $renderKeyField['render']['styletype']);

                    $this->_createSessionStyle($renderKeyField['render']);
                    $arrayDeDatosParaLaPlantilla[$item["name"]] = $render->process($dataField, $item["name"]);
                    $this->_destroySessionStyle();
                }
                else if ($item["valueType"] == "arrayFields") {
                    $typedefKeyField = $this->getTypedefKeyField($item["value"]);
                    $renderKeyField = $this->getRenderKeyField($item["name"]);
                    $render = $this->createRender($typedefKeyField, $renderKeyField);
                    $render->init($item["name"], $renderKeyField['render']['styletype']);

                    $arrayDataField = json_decode($this->getDataField($item["value"]), true);
                    foreach ($arrayDataField as $key) {
                        $arrDataField[$key['ordre']] = $key['nom'];
                    }
                    ksort($arrDataField);

                    if ($item["type"] == "psdom") {
                        $arrDocument = array();
                        foreach ($arrDataField as $dataField) {
                            $this->_createSessionStyle($renderKeyField['render']);
                            $jsonPart = $render->process($dataField, $item["name"]);
                            $this->_destroySessionStyle();
                            if (($arrPart = json_decode($jsonPart))) //evita procesar los documentos inexistentes
                                $arrDocument = array_merge($arrDocument, $arrPart);
                        }
                        $arrayDeDatosParaLaPlantilla[$item["name"]] = json_encode($arrDocument);
                    }
                    else {
                        // Renderiza cada uno de los documentos
                        $htmlDocument = "";
                        foreach ($arrDataField as $dataField) {
                            $this->_createSessionStyle($renderKeyField['render']);
                            $htmlDocument .= $render->process($dataField, $item["name"]);
                            $this->_destroySessionStyle();
                            $toc[] = $this->cfgExport->toc[$item["name"]];
                        }
                        // Une los TOCs de todos los documentos
                        $aux = array();
                        foreach ($toc as $t) {
                            if ($t) $aux = array_merge($aux, $t);
                        }
                        $this->cfgExport->toc[$item["name"]] = $aux;

                        $arrayDeDatosParaLaPlantilla[$item["name"]] = $htmlDocument;
                    }
                }
            }
        }

//        $ret = $this->cocinandoLaPlantillaConDatos($arrayDeDatosParaLaPlantilla);
        self::$deepLevel--;
        return $arrayDeDatosParaLaPlantilla;
    }

    protected function _createSessionStyle($keyRender) {
        $_SESSION['styletype'] = $keyRender['styletype'];
    }
    protected function _destroySessionStyle() {
        unset($_SESSION['styletype']);
    }

    public function getRenderFields() { //devuelve el array de campos establecidos para el render
        $ret = $this->getRenderDef('render')['fields'];
        if (!isset($ret) && $this->factory->getDefaultValueForObjectFields()) {
            $ret = $this->factory->getDefaultValueForObjectFields();
        }
        if (is_string($ret)) {
            switch (strtoupper($ret)) {
                case "ALL":
                    if (!isset($this->typedef["keys"])) {
                        $this->typedef = $this->factory->getTypesDefinition($this->typedef["typeDef"]);
                    }
                    $ret = array_keys($this->typedef["keys"]);
                    break;
                case "MANDATORY":
                    $ret = array();
                    $allKeys = array_keys($this->typedef["keys"]);
                    foreach ($allKeys as $key) {
                        if ($this->typedef["keys"][$key]["mandatory"]) {
                            $ret [] = $key;
                        }
                    }
                    break;
            }
        }
        return $ret;
    }

    public function getRenderExtraFields() { //devuelve el array de campos establecidos para el render
        if (self::$deepLevel==1) {
            $ret = $this->getRenderDef('render')['extraFields'];
        }else {
            $ret = array();
        }
        return $ret;
    }

    public function getDataField($key = NULL) {
        return ($key === NULL) ? $this->data : $this->data[$key];
    }
}

class BasicRenderArray extends renderComposite {

    public function process($data) {
        $ret = array();
        $filter = $this->getFilter();
        $itemType = $this->getItemsType();
        $render = $this->createRender($this->getTypesDefinition($itemType), $this->getTypesRender($itemType));
        //cada $item es un array de tipo concreto en el archivo de datos
        if($filter === "*"){
            $ret = $data;
        }else{
            foreach ($data as $key => $item) {
                if (in_array($key, $filter)) {
                    $ret []= $render->process($item);
                }
            }
        }
        return $ret;
    }

    protected function getItemsType() {
        return $this->getTypeDef('itemsType'); //tipo al que pertenecen los elementos del array
    }
    
    protected function getFilter() {
        $ret = $this->getRenderDef('render')['filter'];
        if(!$ret){
            $ret = "*";
        }
        return $ret;
    }
}

class BasicRenderDate extends AbstractRenderer {
    private $sep;

    public function __construct($factory, $cfgExport=NULL, $sep="-") {
        parent::__construct($factory, $cfgExport);
        $this->sep = $sep;
    }

    public function process($date) {
        $dt = strtotime(str_replace('/', '-', $date));
        return date("d". $this->sep."m".$this->sep."Y", $dt);
    }

}

class BasicRenderText extends AbstractRenderer {

    public function process($data) {
        return htmlentities($data, ENT_QUOTES);
    }
}

class BasicRenderField extends AbstractRenderer {

    public function process($data) {
        return $data;
    }
}

class BasicRenderRenderizableText extends AbstractRenderer {

    public function process($data) {
        $instructions = p_get_instructions($data);
        $html = p_render('wikiiocmodel_ptxhtml', $instructions, $info);
        return $html;
    }
}

class BasicRenderFileToPsDom extends BasicRenderFile {
    protected function render($instructions, &$renderData){
        $ret = p_latex_render('wikiiocmodel_psdom', $instructions, $renderData);
        return $ret;
    }
}

class BasicRenderFile extends AbstractRenderer {

    public function process($data, $alias="") {
        global $plugin_controller;

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
            $startedHere = true;
        }
        $_SESSION['export_html'] = $this->cfgExport->export_html;
        $_SESSION['tmp_dir'] = $this->cfgExport->tmp_dir;
        $_SESSION['latex_images'] = &$this->cfgExport->latex_images;
        $_SESSION['media_files'] = &$this->cfgExport->media_files;
        $_SESSION['graphviz_images'] = &$this->cfgExport->graphviz_images;
        $_SESSION['gif_images'] = &$this->cfgExport->gif_images;
        $_SESSION['alternateAddress'] = TRUE;
        $_SESSION['dir_images'] = "img/";

        if(preg_match("/".$this->cfgExport->id."/", $data)!=1){
            $fns = $this->cfgExport->id.":".$data;
        }
        $file = wikiFN($fns);
        $text = io_readFile($file);

        $counter = 0;
        $text = preg_replace("/~~USE:WIOCCL~~\n/", "", $text, 1, $counter);
        if($counter>0){
            $dataSource = $plugin_controller->getCurrentProjectDataSource($this->cfgExport->id, $plugin_controller->getCurrentProject());
            $text = WiocclParser::getValue($text, [], $dataSource);
        }

        $instructions = p_get_instructions($text);
        $renderData = array();
        $html = $this->render($instructions, $renderData);
        if(empty($alias)){
            $alias=$data;
        }
        $this->cfgExport->toc[$alias] = $renderData["tocItems"];
        if ($startedHere) session_destroy();

        return $html;
    }

    protected function render($instructions, &$renderData){
        return p_render('wikiiocmodel_ptxhtml', $instructions, $renderData);
    }
}


class BasicRenderDocument extends BasicRenderObject{
    public function __construct($factory, $typedef, $renderdef) {
        parent::__construct($factory, $typedef, $renderdef);
    }
    
    public function initParams(){
    }    
    
    public function process($data) {
        $ret = parent::process($data);
        $ret = $this->cocinandoLaPlantillaConDatos($ret);
        return $ret;
    }
    
    public function cocinandoLaPlantillaConDatos($data) {
        $ret = "";
        $isArray = is_array($data);
        $isObject = $isArray && array_keys($data) !== range(0, count($arr) - 1);
//        $fl = true;
        if($isObject){
//            foreach ($data as $k => $v){
//                $sep = $fl?"":", ";
//                $value = $this->cocinandoLaPlantillaConDatos($v);
//                $ret .= "$sep$k: $value";
//                $fl=false;
//            }
            $ret = json_encode($data);
        }else if($isArray){
//            foreach ($data as $v){
//                $sep = $fl?"":",";
//                $value = $this->cocinandoLaPlantillaConDatos($v);
//                $ret .= "$sep$k: $value";
//                $fl=false;
//            }
            $ret = json_encode($data);            
        }else{
            $ret = $data;
        }
        return $ret;
    }

}

class BasicRenderLatexDocument extends BasicRenderDocument{
    protected $ioclangcontinue;
    protected $path_templates;

    public function __construct($factory, $typedef, $renderdef) {
        parent::__construct($factory, $typedef, $renderdef);
    }

    public function initParams(){
        $this->ioclangcontinue = array('CA'=>'continuació', 'DE'=>'fortsetzung', 'EN'=>'continued','ES'=>'continuación','FR'=>'suite','IT'=>'continua');
        $this->path_templates = realpath(__DIR__)."/".$this->factory->getDocumentClass()."/templates";
    }

    /**
     * Replace all reserved symbols
     * @param string $text
     */
    public function clean_accent_chars($text){
        return self::st_clean_accent_chars($text);
    }    

    public static function st_clean_accent_chars($text){
        $source_char = array('á', 'é', 'í', 'ó', 'ú', 'à', 'è', 'ò', 'ï', 'ü', 'ñ', 'ç','Á', 'É', 'Í', 'Ó', 'Ú', 'À', 'È', 'Ò', 'Ï', 'Ü', 'Ñ', 'Ç','\\\\');
        $replace_char = array("\'{a}", "\'{e}", "\'{i}", "\'{o}", "\'{u}", "\`{a}", "\`{e}", "\`{o}", '\"{i}', '\"{u}', '\~{n}', '\c{c}', "\'{A}", "\'{E}", "\'{I}", "\'{O}", "\'{U}", "\`{A}", "\`{E}", "\`{O}", '\"{I}', '\"{U}', '\~{N}', '\c{C}','\break ');
        return str_replace($source_char, $replace_char, $text);
    }    
}

class BasicRenderHtmlDocument extends BasicRenderDocument{
//    protected $max_menu;
//    protected $max_navmenu;
//    protected $media_path = 'lib/exe/fetch.php?media=';
//    protected $menu_html = '';
    //protected $tree_names = array();
//    protected $web_folder = 'WebContent';
    protected $time_start;
    protected $ioclangcontinue;
    protected $initialized = FALSE;

    public function __construct($factory, $typedef, $renderdef) {
        parent::__construct($factory, $typedef, $renderdef);
        $this->cfgExport->rendererPath = $factory->getPathExporterProject();
    }

    public function initParams(){
        $langFile = $this->cfgExport->langDir.$this->cfgExport->lang.'.conf';
        if (!file_exists($langFile)){
            $this->cfgExport->lang = 'ca';
            $langFile = $this->cfgExport->langDir.$this->cfgExport->lang.'.conf';
        }
        if (file_exists($langFile)) {
            $this->cfgExport->aLang = confToHash($langFile);
        }
        $this->initialized = TRUE;
    }
}

/*
{
    align: L|left|R|right|C|center
    padding: [0,+inf) | {top: [0,+inf), right: [0,+inf), bottom: [0,+inf), left: [0,+inf)}
    margin: [0,+inf) | {top: [0,+inf), right: [0,+inf), bottom: [0,+inf), left: [0,+inf)}
    border: [0,+inf) | {top: [0,+inf), right: [0,+inf), bottom: [0,+inf), left: [0,+inf)}
    color: [#000000, #ffffff]
    background-color:[#000000, #ffffff]
    font: (font name list)
    font-size: [0,+inf)    
    pos-x: [0,+inf)
    pos-y: [0,+inf)
    width: [0,+inf)
    height:  [0,+inf)
}
*/

class BasicPdfRenderer {
    protected $tableCounter = 0;
    protected $tableReferences = array();
    protected $tablewidths = array();
    protected $nColInRow = 0;
    protected $figureCounter = 0;
    protected $figureReferences = array();
    protected $headerNum = array(0,0,0,0,0,0);
    protected $headerFont = "helvetica";
    protected $headerFontSize = 10;
    protected $footerFont = "helvetica";
    protected $footerFontSize = 8;
    protected $firstPageFont = "Times";
    protected $pagesFont = "helvetica";
    protected $state = ["table" => ["type" => "table"]];
    protected $tcpdfObj = NULL;
    protected $maxImgSize = 100;

    public function __construct() {
        $this->maxImgSize = WikiGlobalConfig::getConf('max_img_size', 'wikiiocmodel');
    }

    public function resetDataRender() {
        $this->tableCounter = 0;
        $this->tableReferences = array();
        $this->tablewidths = array();
        $this->nColInRow = 0;
        $this->figureCounter = 0;
        $this->figureReferences = array();
        $this->headerNum = array(0,0,0,0,0,0);
        $this->headerFont = "helvetica";
        $this->headerFontSize = 10;
        $this->footerFont = "helvetica";
        $this->footerFontSize = 8;
        $this->firstPageFont = "Times";
        $this->pagesFont = "helvetica";
        $this->state = ["table" => ["type" => "table"]];
    }

    protected function setMaxImgSize($max_img_size) {
        $this->maxImgSize = $max_img_size;
    }

    protected function getMaxImgSize() {
        return $this->maxImgSize;
    }

    protected function resolveReferences($content) {
        if (!empty($content["id"])) {
            if ($content["type"]===TableFrame::TABLEFRAME_TYPE_TABLE || $content["type"]===TableFrame::TABLEFRAME_TYPE_ACCOUNTING) {
                $this->tableCounter++;
                $this->tableReferences[$content["id"]] = $this->tableCounter;
            }elseif ($content["type"]===FigureFrame::FRAME_TYPE_FIGURE) {
                $this->figureCounter++;
                $this->figureReferences[$content["id"]] = $this->figureCounter;
            }
        }
        if (!empty($content["content"])) {
            for ($i=0; $i<count($content["content"]); $i++) {
                self::resolveReferences($content["content"][$i]);
            }
        }
        if (!empty($content["children"])) {
            for ($i=0; $i<count($content["children"]); $i++) {
                self::resolveReferences($content["children"][$i]);
            }
        }
    }

    protected function renderHeader($header, IocTcPdf &$iocTcPdf) {
        if ($header['type'] !== StructuredNodeDoc::ROOTCONTENT_TYPE) {
            $level = $header["level"]-1;
            $iocTcPdf->SetFont('Times', 'B', 12);
            $title = self::incHeaderCounter($level).$header["title"];

            //Control de espacio disponible para impedir títulos huérfanos
            if ($iocTcPdf->GetY() + 40 > $iocTcPdf->getPageHeight()) {
                $iocTcPdf->AddPage(); //inserta salto de pagina
            }

            $iocTcPdf->Bookmark($title, $level, 0);
            $iocTcPdf->Ln(5);
            $iocTcPdf->Cell(0, 0, $title, 0,1, "L");
            $iocTcPdf->Ln(3);
        }

        if (!empty($header["content"])) {
            for ($i=0; $i<count($header["content"]); $i++) {
                self::renderContent($header["content"][$i], $iocTcPdf);
            }
        }
        if (!empty($header["children"])) {
            for ($i=0; $i<count($header["children"]); $i++) {
                self::renderHeader($header["children"][$i], $iocTcPdf);
            }
        }
    }

    protected function getHeaderCounter($level) {
        $ret = "";
        for ($i=0; $i<=$level; $i++) {
            $ret .= $this->headerNum[$i].".";
        }
        return $ret." ";
    }

    protected function incHeaderCounter($level) {
        $this->headerNum[$level]++;
        for ($i=$level+1; $i<count($this->headerNum); $i++) {
            $this->headerNum[$i]=0;
        }
        return self::getHeaderCounter($level);
    }

    protected function renderContent($content, IocTcPdf &$iocTcPdf, $pre="", $post="") {
        $iocTcPdf->SetFont('helvetica', '', 10);
        if ($content['type'] === FigureFrame::FRAME_TYPE_FIGURE) {
            self::getFrameContent($content, $iocTcPdf);
        }/*
        elseif ($content['type'] === StructuredNodeDoc::PARAGRAPH_TYPE && $content['content'][0]['type'] === ImageNodeDoc::IMAGE_TYPE) {
            self::renderImage($content, $iocTcPdf);
        }
        elseif ($content['type'] === ImageNodeDoc::IMAGE_TYPE) {
            self::renderImage($content, $iocTcPdf);
        }
        elseif ($content['type'] === SmileyNodeDoc::SMILEY_TYPE) {
            self::renderSmiley($content, $iocTcPdf);
        }*/
        else {
            $ret = static::getContent($content);
            self::_cleanWriteHTML($ret, $iocTcPdf);
        }

        if ($content["type"] == StructuredNodeDoc::ORDERED_LIST_TYPE
                || $content["type"] == StructuredNodeDoc::UNORDERED_LIST_TYPE
                || $content["type"] == StructuredNodeDoc::PARAGRAPH_TYPE) {
            $iocTcPdf->Ln(3);
        }
    }

    protected function getFrameContent($content, IocTcPdf &$iocTcPdf) {
        switch ($content['type']) {
            case ImageNodeDoc::IMAGE_TYPE:
                self::renderImage($content, $iocTcPdf);
                break;

            case FigureFrame::FRAME_TYPE_FIGURE:
                // Comprueba si queda suficiente espacio vertical para poner la imagen
                // junto al título, es decir, si cabe el bloque título + imagen en el resto de página
                list($w, $h) = self::setImageSize($content['content'][0]['content'][0]['src'], $content['content'][0]['content'][0]['width'], $content['content'][0]['content'][0]['height']);
                if ($iocTcPdf->GetY() + $h + 25 > $iocTcPdf->getPageHeight()) {
                    $iocTcPdf->AddPage(); //inserta salto de pagina
                }
                $center = "style=\"margin:auto; text-align:center;";
                if ($content["hasBorder"]) {
                    $style = $center . " border:1px solid gray;";
                }
                $ret = "<div $style nobr=\"true\">";
                if ($content['title']) {
                    $ret .= "<p $center font-weight:bold;\">Figura ".$this->figureReferences[$content['id']].". ".$content['title']."</p>";
                }
                self::_cleanWriteHTML($ret, $iocTcPdf);
                $ret = self::getFrameStructuredContent($content, $iocTcPdf);
                if ($content['footer']) {
                    if ($content['title']) {
                        $ret .= "<p $center font-size:80%;\">".$content['footer']."</p>";
                    }else {
                        $ret .= "<p $center font-size:80%;\">Figura ".$this->figureReferences[$content['id']].". ".$content['footer']."</p>";
                    }
                }
                $ret .= "</div>";
                self::_cleanWriteHTML($ret, $iocTcPdf);
                break;

            default:
                self::getFrameStructuredContent($content, $iocTcPdf);
                break;
        }
        return "";
    }

    protected function getFrameStructuredContent($content, IocTcPdf &$iocTcPdf) {
        $ret = "";
        $limit = count($content['content']);
        for ($i=0; $i<$limit; $i++) {
            $ret .= self::getFrameContent($content['content'][$i], $iocTcPdf);
        }
        return $ret;
    }

    /**
     * Neteja de caracters indesitjables el text que s'envia a ser codificat com a PDF
     * @param $content (string) text to convert
     * @param $iocTcPdf (IocTcPdf)
     * @param $ln (boolean) if true add a new line after text (default = true)
     * @param $fill (boolean) Indicates if the background must be painted (true) or transparent (false).
     * @param $reseth (boolean) if true reset the last cell height (default false).
     * @param $cell (boolean) if true add the current left (or right for RTL) padding to each Write (default false).
     * @param $align (string) Allows to center or align the text. Possible values are: L:left align - C:center - R:right align - empty:left for LTR or right for RTL
     */
    private function _cleanWriteHTML($content, IocTcPdf &$iocTcPdf, $ln=TRUE, $fill=FALSE, $reseth=false, $cell=false, $align='') {
        $c = 0;
        $aSearch = ["/0xFF/", "/0xFEFF/"];
        $aReplace = [" ", " "];
        $content = preg_replace($aSearch, $aReplace, $content, -1, $c);
        if ($c > 0) {
            $content = str_replace($aSearch, $aReplace, $content);
        }
        $iocTcPdf->writeHTML($content, $ln, $fill, $reseth, $cell, $align);
    }

    private function renderSmiley($content, IocTcPdf &$iocTcPdf) {
        preg_match('/\.(.+)$/', $content['src'], $match);
        $ext = ($match) ? $match[1] : "JPG";
        $iocTcPdf->Image($content['src'], '', '', 0, 0, $ext, '', 'T');
    }

    protected function renderImage($content, IocTcPdf &$iocTcPdf) {
        preg_match('/\.(.+)$/', $content['src'], $match);
        $ext = ($match) ? $match[1] : "JPG";
        //càlcul de les dimensions de la imatge
        list($w, $h) = self::setImageSize($content['src'], $content['width'], $content['height']);
        if ($iocTcPdf->GetY() + $h > $iocTcPdf->getPageHeight()) {
            $iocTcPdf->AddPage(); //inserta salto de pagina
        }
        //inserció de la imatge
        $iocTcPdf->Image($content['src'], '', '', $w, 0, $ext, '', 'T', true, 300, 'C');
        $iocTcPdf->SetY($iocTcPdf->GetY() + $h); //correcció de la coordinada Y desprès de insertar la imatge
        //inserció del títol a sota de la imatge
        $center = "style=\"margin:auto; text-align:center;";
        $text = "<p $center font-size:80%;\">{$content['title']}</p>";
        self::_cleanWriteHTML($text, $iocTcPdf);
    }

    private function setImageSize($imageFile, $w=NULL, $h=NULL) {
        if (@file($imageFile)) {
            list($w0, $h0) = getimagesize($imageFile);
        }
        if ($w0 == NULL) {
            $w0 = $h0 = 5;
        }

        if ($w==NULL) {
            if ($w0 <= 800) {
                $w = $w0;
            }else {
                $factor_reduc = 800 / $w0;
                $w = 800;
            }
        }else {
            $factor_reduc = $w / $w0;
        }
        if ($h==NULL) {
            $h = ($factor_reduc!=NULL) ? $h0*$factor_reduc : $h0;
            if ($h > 1200) {
                $factor_reduc = 1200 / $h;
                $h = 1200;
                $w = $w * $factor_reduc;
            }
        }
        return [$w/5, $h/5];
    }

    private function getImgReduction($file, $p) {
        list($w, $h) = getimagesize($file);
        if ($w > self::getMaxImgSize()) {
            $wreduc = self::getMaxImgSize() / $w;
        }
        if ($h > self::getMaxImgSize()) {
            $hreduc = self::getMaxImgSize() / $h;
        }
        $r0 = ($wreduc < $hreduc) ? $wreduc : $hreduc;

        $wreduc = $hreduc = 1;
        if ($p['w'] && $p['w'] > self::getMaxImgSize()) {
            $wreduc = self::getMaxImgSize() / $p['w'];
        }
        if ($p['h'] && $p['h'] > self::getMaxImgSize()) {
            $hreduc = self::getMaxImgSize() / $p['h'];
        }
        $r1 = ($wreduc < $hreduc) ? $wreduc : $hreduc;

        return ($r0 < $r1) ? $r0 : $r1;
    }

    protected function getContent($content ) {
        $pre_active=false;
        $aux="";
        $char = "";
        $ret = "";
        switch ($content["type"]) {
            case ListItemNodeDoc::LIST_ITEM_TYPE:
                $ret = "<li style=\"text-align:justify;\">".trim(self::getStructuredContent($content), " ")."</li>";
                break;
            case StructuredNodeDoc::DELETED_TYPE:
                $ret = "<del>".self::getStructuredContent($content)."</del>";
                break;
            case StructuredNodeDoc::EMPHASIS_TYPE:
                $ret = "<em>".self::getStructuredContent($content)."</em>";
                break;
            case StructuredNodeDoc::FOOT_NOTE_TYPE:
                break;
            case StructuredNodeDoc::LIST_CONTENT_TYPE:
                break;
            case StructuredNodeDoc::MONOSPACE_TYPE:
                $ret = "<code>".self::getStructuredContent($content)."</code>";
                break;
            case StructuredNodeDoc::ORDERED_LIST_TYPE:
                $ret = "<ol>".self::getStructuredContent($content)."</ol>";
                break;
            case StructuredNodeDoc::PARAGRAPH_TYPE:
                $ret = '<p style="text-align:justify;">'.trim(self::getStructuredContent($content), " ").'</p>';
                break;
            case StructuredNodeDoc::SINGLEQUOTE_TYPE:
                $char = "'";
            case StructuredNodeDoc::DOUBLEQUOTE_TYPE:
                $char = empty($char) ? "\"" : $char;
                $ret = $char.self::getStructuredContent($content).$char;
                break;
            case StructuredNodeDoc::QUOTE_TYPE:
                $ret = "<blockquote>".self::getStructuredContent($content)."</blockquote>";
                break;
            case StructuredNodeDoc::STRONG_TYPE:
                $ret = "<strong>".self::getStructuredContent($content)."</strong>";
                break;
            case StructuredNodeDoc::SUBSCRIPT_TYPE:
                $ret = "<sub>".self::getStructuredContent($content)."</sub>";
                break;
            case StructuredNodeDoc::SUPERSCRIPT_TYPE:
                $ret = "<sup>".self::getStructuredContent($content)."</sup>";
                break;
            case StructuredNodeDoc::UNDERLINE_TYPE:
                $ret = "<u>".self::getStructuredContent($content)."</u>";
                break;
            case StructuredNodeDoc::UNORDERED_LIST_TYPE:
                $ret = "<ul>".self::getStructuredContent($content)."</ul>";
                break;
            case SpecialBlockNodeDoc::HIDDENCONTAINER_TYPE:
                $ret = '<span style="color:gray; font-size:80%;">' . self::getStructuredContent($content) . '</span>';
                break;

            case LatexMathNodeDoc::LATEX_MATH_TYPE:
                $div = $nodiv = "";
                if ($content['class'] === 'blocklatex') {
                    $div = "<div style=\"margin:auto; text-align:center;\">";
                    $nodiv = "</div>";
                }
                preg_match("|.*".DOKU_BASE."(.*)|", $content["src"], $t);
                $ret = $div . ' <img src="'.DOKU_BASE.$t[1].'"';
                if ($content["title"])
                    $ret.= ' alt="'.$content["title"].'"';
                if ($content["width"])
                    $ret.= ' width="'.$content["width"].'"';
                if ($content["height"])
                    $ret.= ' height="'.$content["height"].'"';
                $ret.= '> ' . $nodiv;
                break;

            case ImageNodeDoc::IMAGE_TYPE:
                if (preg_match("|\.gif$|", $content["src"], $t)) {
                    //El formato GIF no está soportado
                    $ret = " {$content["title"]} ";
                }else {
                    preg_match("|.*".DOKU_BASE."(.*)|", $content["src"], $t);
                    $reduc = self::getImgReduction($content["src"], ['w'=>$content["width"], 'h'=>$content["height"]]);

                    $ret = ' <img src="'.DOKU_BASE.$t[1].'"';
                    if ($content["title"])
                        $ret.= ' alt="'.$content["title"].'"';
                    if ($content["width"])
                        $ret.= ' width="' . $content["width"]*$reduc . '"';
                    if ($content["height"])
                        $ret.= ' height="' . $content["height"]*$reduc . '"';
                    $ret.= '> ';
                }
                break;

            case LeafNodeDoc::ACRONYM_TYPE:
                $ret = $content['acronym'];
                break;
            case SmileyNodeDoc::SMILEY_TYPE:
                preg_match("|.*".DOKU_BASE."(.*)|", $content["src"], $t);
                $ret = ' <img src="'.DOKU_BASE.$t[1].'" alt="smiley" height="8" width="8"> ';
                break;

            case SpecialBlockNodeDoc::NEWCONTENT_TYPE:
                //$ret = '<div style="border:1px solid red; padding:0 10px; margin:5px 0;">' . self::getStructuredContent($content) . "</div>";
            case SpecialBlockNodeDoc::BLOCVERD_TYPE:
                //$ret = '<span style="background-color:lightgreen;">' . self::getStructuredContent($content) . '</span>';
            case SpecialBlockNodeDoc::PROTECTED_TYPE:
            case SpecialBlockNodeDoc::SOL_TYPE:
            case SpecialBlockNodeDoc::SOLUCIO_TYPE:
            case SpecialBlockNodeDoc::VERD_TYPE:
            case SpecialBlockNodeDoc::EDITTABLE_TYPE:
                $ret = self::getStructuredContent($content);
                break;
            case IocElemNodeDoc::IOC_ELEM_TYPE:
                switch ($content["elemType"]){
                    case IocElemNodeDoc::IOC_ELEM_TYPE_EXAMPLE:
                        $aux=" font-size: 120%;";
                    case IocElemNodeDoc::IOC_ELEM_TYPE_COMP_LARGE:
                        if($content["type"]=== IocElemNodeDoc::IOC_ELEM_TYPE_EXAMPLE){
                            $bc = "";
                        }else{
                            $bc = " background-color: #efefef;";
                        }
                        $p_style="style=\"margin-bottom: 2em;$aux\"";
                        $title = $content["title"];
                        $ret = "<div nobr=\"true\" style=\"width: 73%;\">";
                        $ret .= "<div style=\"clear:both;$bc width: 80%; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; margin: 1em auto; font-size: 85%;\">";
                        $ret .= "<p $p_style>$title<\p>";
                        $ret .= self::getStructuredContent($content);
                        $ret .= "</div></div>";
                        break;
                    case IocElemNodeDoc::IOC_ELEM_TYPE_QUOTE:
                    case IocElemNodeDoc::IOC_ELEM_TYPE_IMPORTANT:
                        if($content["type"]=== IocElemNodeDoc::IOC_ELEM_TYPE_QUOTE){
                            $bc = " background-color: #efefef; padding: 1.5em 4.5em 2.5em 1.5em; font-size: 85%; color: #2c2c2c; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc;";
                        }else{
                            $bc = " background-color: #ccc; padding: 10mm;";
                        }
                        $ret = "<div nobr=\"true\" style=\"clear:both; width: 80%;$bc margin: 10mm auto;\">";
                        $ret .= self::getStructuredContent($content);
                        $ret .= "</div>";
                        break;
                    case IocElemNodeDoc::IOC_ELEM_TYPE_COMP:
                    case IocElemNodeDoc::IOC_ELEM_TYPE_NOTE:
                    case IocElemNodeDoc::IOC_ELEM_TYPE_REF:
                        break;
                }
                break;
            case TableFrame::TABLEFRAME_TYPE_TABLE:
            case TableFrame::TABLEFRAME_TYPE_ACCOUNTING:
                if ($content['widths']) {
                    $e = explode(',', $content['widths']);
                    $t = 0;
                    for ($i=0; $i<count($e); $i++) $t += $e[$i];
                    for ($i=0; $i<count($e); $i++) $this->tablewidths[$i] = $e[$i] * 100 / $t;
                }
                $ret = "<div nobr=\"true\">";
                if ($content["title"]) {
                    $ret .= "<h4 style=\"text-align:center;\"> Taula ".$this->tableReferences[$content["id"]].". ".$content["title"]."</h4>";
                }
                $ret .= self::getStructuredContent($content);
                if ($content["footer"]) {
                    if ($content["title"]) {
                        $ret .= "<p style=\"text-align:justify; font-size:80%;\">".$content["footer"]."</p>";
                    }else {
                        $ret .= "<p style=\"text-align:justify; font-size:80%;\"> Taula ".$this->tableReferences[$content["id"]].". ".$content["footer"]."</p>";
                    }
                }
                $ret .= "</div>";
                break;
            case TableNodeDoc::TABLE_TYPE:
                $ret = '<table cellpadding="5" nobr="true">'.self::getStructuredContent($content)."</table>";
                break;
            case StructuredNodeDoc::TABLEROW_TYPE:
                $ret = "<tr>".self::getStructuredContent($content)."</tr>";
                $this->nColInRow = 0;
                break;
            case CellNodeDoc::TABLEHEADER_TYPE:
                $align = $content["align"] ? "text-align:{$content["align"]};" : "text-align:center;";
                $style = $content["hasBorder"] ? ' style="border:1px solid black; border-collapse:collapse; '.$align.' font-weight:bold; background-color:#F0F0F0;"' : ' style="'.$align.' font-weight:bold; background-color:#F0F0F0;"';
                $colspan = $content["colspan"]>1 ? ' colspan="'.$content["colspan"].'"' : "";
                $rowspan = $content["rowspan"]>1 ? ' rowspan="'.$content["rowspan"].'"' : "";
                $ret = "<th$colspan$rowspan$style>".self::getStructuredContent($content)."</th>";
                break;
            case CellNodeDoc::TABLECELL_TYPE:
                $align = $content["align"] ? "text-align:{$content["align"]};" : "text-align:center;";
                $style = $content["hasBorder"] ? ' style="border:1px solid black; border-collapse:collapse; '.$align.'"' : " style=\"$align\"";
                $colspan = $content["colspan"]>1 ? ' colspan="'.$content["colspan"].'"' : "";
                $rowspan = $content["rowspan"]>1 ? ' rowspan="'.$content["rowspan"].'"' : "";
                $width =  ($this->tablewidths[$this->nColInRow++]) ? ' with="'.$this->tablewidths[$this->nColInRow++].'%"' : "";
                $ret = "<td$colspan$rowspan$style$width>".self::getStructuredContent($content)."</td>";
                break;
            case TextNodeDoc::HTML_TEXT_TYPE:
                $ret = self::getTextContent($content);
                break;
            case TextNodeDoc::PLAIN_TEXT_TYPE:
                $ret = self::getTextContent($content);
                break;

            case ReferenceNodeDoc::REFERENCE_TYPE:
                $titol = (empty($content["referenceTitle"])) ? $content["referenceId"] : $content["referenceTitle"];
                switch ($content["referenceType"]) {
                    case ReferenceNodeDoc::REF_TABLE_TYPE:
                        $id = trim($content["referenceId"]);
                        $ret = " <a href=\"#".$id."\"><em>Taula ".$this->tableReferences[$id]."</em></a> ";
                        break;
                    case ReferenceNodeDoc::REF_FIGURE_TYPE:
                        $id = trim($content["referenceId"]);
                        $ret = " <a href=\"#".$id."\"><em>Figura ".$this->figureReferences[$id]."</em></a> ";
                        break;
                    case ReferenceNodeDoc::REF_WIKI_LINK:
                        $file = $_SERVER['HTTP_REFERER']."?id=".$content["referenceId"];
                        $ret = " <a href=\"".$file."\">".$titol."</a> ";
                        break;
                    case ReferenceNodeDoc::REF_INTERNAL_LINK:
                        $ret = " <a href='".$content["referenceId"]."'>".$titol."</a> ";
                        break;
                    case ReferenceNodeDoc::REF_EXTERNAL_LINK:
                        $ret = " <a href=\"".$content["referenceId"]."\">".$titol."</a> ";
                        break;
                }
                break;

            case CodeNodeDoc::CODE_TEXT_TYPE:
                //$content["text"] = p_xhtml_cached_geshi($content["text"], $content["language"], "code");
            case TextNodeDoc::UNFORMATED_TEXT_TYPE:
            case TextNodeDoc::PREFORMATED_TEXT_TYPE:
                $ret = "<pre>".self::getPreformatedTextContent($content)."</pre>";
                break;
            default :
                $ret = self::getLeafContent($content);
        }
        return $ret;
    }

    protected function getStructuredContent($content) {
        $ret = "";
        $limit = count($content["content"]);
        for ($i=0; $i<$limit; $i++) {
            $ret .= static::getContent($content["content"][$i]);
        }
        return $ret;
    }

    protected function getPreformatedTextContent($content) {
        if (!empty($content["text"]) && empty(trim($content["text"]))) {
            $ret = " ";
        }else {
            $ret = preg_replace(array("/<br>/"), array("\n"), trim($content["text"]));  
            $ret = htmlspecialchars($ret, ENT_QUOTES, 'UTF-8');
        }
        return $ret;
    }
    
    protected function getTextContent($content) {
        if (!empty($content["text"]) && empty(trim($content["text"]))) {
            $ret = " ";
        }else {
            $ret = preg_replace("/\s\s+/", " ", $content["text"]);
            $ret = htmlspecialchars($ret, ENT_QUOTES, 'UTF-8');
        }
        return $ret;
    }

    protected function getLeafContent($content) {
        switch($content["type"]) {
            case LeafNodeDoc::HORIZONTAL_RULE_TYPE:
                $ret = "<hr>";
                break;
            case LeafNodeDoc::LINE_BREAK_TYPE:
                $ret = "<br>";
                break;
            case LeafNodeDoc::DOUBLEAPOSTROPHE_TYPE:
                $ret = "\"";
                break;
            case LeafNodeDoc::APOSTROPHE_TYPE:
            case LeafNodeDoc::OP_SINGLEQUOTE_TYPE:
            case LeafNodeDoc::CL_SINGLEQUOTE_TYPE:
                $ret = "'";
                break;
            case LeafNodeDoc::BACKSLASH_TYPE:
                $ret = "\\";
                break;
            case LeafNodeDoc::DOUBLEHYPHEN_TYPE:
                $ret = "&mdash;";
                break;
            case LeafNodeDoc::GRAVE_TYPE:
                $ret = "&#96;";
                break;
        }
        return $ret;
    }

    public static function getText($text, $max, IocTcPdf &$iocTcPdf){
        if($iocTcPdf->GetStringWidth($text)>$max){
            while($iocTcPdf->GetStringWidth($text."...")>$max){
                $text = substr($text, 0, strlen($text)-1);
            }
            $text = $text."...";
        }
        return $text;
    }

}
