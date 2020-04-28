<?php
/**
 * BasicExporterClasses: clases de procesos, establecidas en el fichero de configuración,
 *                       correspondientes a los tipos de datos del proyecto
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', realpath(DOKU_INC."lib/plugins/"));
if (!defined('EXPORT_TMP')) define('EXPORT_TMP', DOKU_PLUGIN."tmp/latex/");

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

        $ret = $this->cocinandoLaPlantillaConDatos($arrayDeDatosParaLaPlantilla);
        self::$deepLevel--;
        return $ret;
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

    public function cocinandoLaPlantillaConDatos($param) {
        if (is_array($param)) {
            foreach ($param as $value) {
                $ret .= (is_array($value)) ? $this->cocinandoLaPlantillaConDatos($value)."\n" : $value."\n";
            }
        }else {
            $ret = $param;
        }
        return $ret;
    }
}

class renderArray extends renderComposite {

    public function process($data) {
        $ret = "";
        $filter = $this->getFilter();
        $itemType = $this->getItemsType();
        $render = $this->createRender($this->getTypesDefinition($itemType), $this->getTypesRender($itemType));
        //cada $item es un array de tipo concreto en el archivo de datos
        foreach ($data as $key => $item) {
            if ($filter === "*" || in_array($key, $filter)) {
                $ret .= $render->process($item);
            }
        }
        return $ret;
    }

    protected function getItemsType() {
        return $this->getTypeDef('itemsType'); //tipo al que pertenecen los elementos del array
    }
    protected function getFilter() {
        return $this->getRenderDef('render')['filter'];
    }
}

class BasicStaticPdfRenderer {
    static $tableCounter = 0;
    static $tableReferences = array();
    static $tablewidths = array();
    static $nColInRow = 0;
    static $figureCounter = 0;
    static $figureReferences = array();
    static $headerNum = array(0,0,0,0,0,0);
    static $headerFont = "helvetica";
    static $headerFontSize = 10;
    static $footerFont = "helvetica";
    static $footerFontSize = 8;
    static $firstPageFont = "Times";
    static $pagesFont = "helvetica";
    static $state = ["table" => ["type" => "table"]];
    static $tcpdfObj = NULL;
    static $maxImgSize = 100;

    public function __construct() {
        self::$maxImgSize = WikiGlobalConfig::getConf('max_img_size', 'wikiiocmodel');
    }

    public static function resetStaticDataRender() {
        self::$tableCounter = 0;
        self::$tableReferences = array();
        self::$tablewidths = array();
        self::$nColInRow = 0;
        self::$figureCounter = 0;
        self::$figureReferences = array();
        self::$headerNum = array(0,0,0,0,0,0);
        self::$headerFont = "helvetica";
        self::$headerFontSize = 10;
        self::$footerFont = "helvetica";
        self::$footerFontSize = 8;
        self::$firstPageFont = "Times";
        self::$pagesFont = "helvetica";
        self::$state = ["table" => ["type" => "table"]];
    }

    protected static function setMaxImgSize($max_img_size) {
        self::$maxImgSize = $max_img_size;
    }

    protected static function getMaxImgSize() {
        return self::$maxImgSize;
    }

    protected static function resolveReferences($content) {
        if (!empty($content["id"])) {
            if ($content["type"]===TableFrame::TABLEFRAME_TYPE_TABLE || $content["type"]===TableFrame::TABLEFRAME_TYPE_ACCOUNTING) {
                self::$tableCounter++;
                self::$tableReferences[$content["id"]] = self::$tableCounter;
            }elseif ($content["type"]===FigureFrame::FRAME_TYPE_FIGURE) {
                self::$figureCounter++;
                self::$figureReferences[$content["id"]] = self::$figureCounter;
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

    protected static function renderHeader($header, IocTcPdf &$iocTcPdf) {
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

    protected static function getHeaderCounter($level) {
        $ret = "";
        for ($i=0; $i<=$level; $i++) {
            $ret .= self::$headerNum[$i].".";
        }
        return $ret." ";
    }

    protected static function incHeaderCounter($level) {
        self::$headerNum[$level]++;
        for ($i=$level+1; $i<count(self::$headerNum); $i++) {
            self::$headerNum[$i]=0;
        }
        return self::getHeaderCounter($level);
    }

    protected static function renderContent($content, IocTcPdf &$iocTcPdf, $pre="", $post="") {
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
            $iocTcPdf->writeHTML($ret, TRUE, FALSE);
        }

        if ($content["type"] == StructuredNodeDoc::ORDERED_LIST_TYPE
                || $content["type"] == StructuredNodeDoc::UNORDERED_LIST_TYPE
                || $content["type"] == StructuredNodeDoc::PARAGRAPH_TYPE) {
            $iocTcPdf->Ln(3);
        }
    }

    protected static function getFrameContent($content, IocTcPdf &$iocTcPdf) {
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
                    $ret .= "<p $center font-weight:bold;\">Figura ".self::$figureReferences[$content['id']].". ".$content['title']."</p>";
                }
                $iocTcPdf->writeHTML($ret, TRUE, FALSE);
                $ret = self::getFrameStructuredContent($content, $iocTcPdf);
                if ($content['footer']) {
                    if ($content['title']) {
                        $ret .= "<p $center font-size:80%;\">".$content['footer']."</p>";
                    }else {
                        $ret .= "<p $center font-size:80%;\">Figura ".self::$figureReferences[$content['id']].". ".$content['footer']."</p>";
                    }
                }
                $ret .= "</div>";
                $iocTcPdf->writeHTML($ret, TRUE, FALSE);
                break;

            default:
                self::getFrameStructuredContent($content, $iocTcPdf);
                break;
        }
        return "";
    }

    protected static function getFrameStructuredContent($content, IocTcPdf &$iocTcPdf) {
        $ret = "";
        $limit = count($content['content']);
        for ($i=0; $i<$limit; $i++) {
            $ret .= self::getFrameContent($content['content'][$i], $iocTcPdf);
        }
        return $ret;
    }

    private static function renderSmiley($content, IocTcPdf &$iocTcPdf) {
        preg_match('/\.(.+)$/', $content['src'], $match);
        $ext = ($match) ? $match[1] : "JPG";
        $iocTcPdf->Image($content['src'], '', '', 0, 0, $ext, '', 'T');
    }

    protected static function renderImage($content, IocTcPdf &$iocTcPdf) {
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
        $iocTcPdf->writeHTML($text, TRUE, FALSE);
    }

    private static function setImageSize($imageFile, $w=NULL, $h=NULL) {
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

    private static function getImgReduction($file, $p) {
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

    protected static function getContent($content ) {
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

            case TableFrame::TABLEFRAME_TYPE_TABLE:
            case TableFrame::TABLEFRAME_TYPE_ACCOUNTING:
                if ($content['widths']) {
                    $e = explode(',', $content['widths']);
                    $t = 0;
                    for ($i=0; $i<count($e); $i++) $t += $e[$i];
                    for ($i=0; $i<count($e); $i++) self::$tablewidths[$i] = $e[$i] * 100 / $t;
                }
                $ret = "<div nobr=\"true\">";
                if ($content["title"]) {
                    $ret .= "<h4 style=\"text-align:center;\"> Taula ".self::$tableReferences[$content["id"]].". ".$content["title"]."</h4>";
                }
                $ret .= self::getStructuredContent($content);
                if ($content["footer"]) {
                    if ($content["title"]) {
                        $ret .= "<p style=\"text-align:justify; font-size:80%;\">".$content["footer"]."</p>";
                    }else {
                        $ret .= "<p style=\"text-align:justify; font-size:80%;\"> Taula ".self::$tableReferences[$content["id"]].". ".$content["footer"]."</p>";
                    }
                }
                $ret .= "</div>";
                break;
            case TableNodeDoc::TABLE_TYPE:
                $ret = '<table cellpadding="5" nobr="true">'.self::getStructuredContent($content)."</table>";
                break;
            case StructuredNodeDoc::TABLEROW_TYPE:
                $ret = "<tr>".self::getStructuredContent($content)."</tr>";
                self::$nColInRow = 0;
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
                $width =  (self::$tablewidths[self::$nColInRow++]) ? ' with="'.self::$tablewidths[self::$nColInRow++].'%"' : "";
                $ret = "<td$colspan$rowspan$style$width>".self::getStructuredContent($content)."</td>";
                break;
            case CodeNodeDoc::CODE_TEXT_TYPE:
                $ret = self::getTextContent($content);
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
                        $ret = " <a href=\"#".$id."\"><em>Taula ".self::$tableReferences[$id]."</em></a> ";
                        break;
                    case ReferenceNodeDoc::REF_FIGURE_TYPE:
                        $id = trim($content["referenceId"]);
                        $ret = " <a href=\"#".$id."\"><em>Figura ".self::$figureReferences[$id]."</em></a> ";
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

            case TextNodeDoc::PREFORMATED_TEXT_TYPE:
                $ret = self::getTextContent($content);
                break;
            case TextNodeDoc::UNFORMATED_TEXT_TYPE:
                $ret = self::getTextContent($content);
                break;
            default :
                $ret = self::getLeafContent($content);
        }
        return $ret;
    }

    protected static function getStructuredContent($content) {
        $ret = "";
        $limit = count($content["content"]);
        for ($i=0; $i<$limit; $i++) {
            $ret .= static::getContent($content["content"][$i]);
        }
        return $ret;
    }

    protected static function getTextContent($content) {
        if (!empty($content["text"]) && empty(trim($content["text"]))) {
            $ret = " ";
        }else {
            $ret = preg_replace("/\s\s+/", " ", $content["text"]);
        }
        return $ret;
    }

    protected static function getLeafContent($content) {
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