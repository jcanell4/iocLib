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
    protected $output_filename;

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
    public $figure_references = array();
    public $table_references = array();

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
        if ($ret) {
            while ($typeDef = $this->getTypesDefinition($ret["type"])) {
                $ret = array_merge($ret, $typeDef);
            }
        }else {
            throw new Exception("ERROR: function getTypedefKeyField(): el camp $field no existeix");
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
        if ($campos) {
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
                else if ($item["valueType"] == "arrayDocuments") {
                    $typedefKeyField = $this->getTypedefKeyField($item["value"]);
                    $renderKeyField = $this->getRenderKeyField($item["name"]);
                    $render = $this->createRender($typedefKeyField, $renderKeyField);
                    $render->init($item["name"], $renderKeyField['render']['styletype']);

                    $arrayDataField = json_decode($this->getDataField($item["value"]), true);
                    foreach ($arrayDataField as $key) {
                        $arrDataField[] = $key['nom'];
                    }

                    if ($item["type"] == "psdom") {
                        foreach ($arrDataField as $doc) {
                            $this->_createSessionStyle($renderKeyField['render']);
                            $jsonDoc = $render->process($doc, $item["name"]);
                            $this->_destroySessionStyle();
                            if (!empty($jsonDoc)) {//evita procesar los documentos inexistentes
                                $arrayDeDatosParaLaPlantilla['arrayDocuments'][$doc][$item['name']] = $jsonDoc;
                            }
                        }
                    }else {
                        // Renderiza cada uno de los documentos
                        $htmlDocument = "";
                        foreach ($arrDataField as $doc) {
                            $this->_createSessionStyle($renderKeyField['render']);
                            $htmlDocument = $render->process($doc, $item["name"]);
                            $this->_destroySessionStyle();
                            if (!empty($htmlDocument)) {//evita procesar los documentos inexistentes
                                $arrayDeDatosParaLaPlantilla['arrayDocuments'][$doc][$item['name']] = $htmlDocument;
                                $toc[$doc] = $this->cfgExport->toc[$item["name"]];
                                
                                $latexImg[$doc] = $this->cfgExport->latex_images;
                                $this->cfgExport->latex_images = array();
                                $mediaFiles[$doc] = $this->cfgExport->media_files;
                                $this->cfgExport->media_files = array();
                                $graphvizImg[$doc]= $this->cfgExport->graphviz_images;
                                $this->cfgExport->graphviz_images = array();
                                $gifImg[$doc] = $this->cfgExport->gif_images;
                                $this->cfgExport->gif_images = array();
                                $figRef[$doc] = $this->cfgExport->figure_references;
                                $this->cfgExport->figure_references = array();
                                $tabRef[$doc] = $this->cfgExport->table_references;
                                $this->cfgExport->table_references = array();
                            }
                        }
                        $this->cfgExport->toc = $toc;
                        
                        $this->cfgExport->latex_images = $latexImg;
                        $this->cfgExport->media_files = $mediaFiles;
                        $this->cfgExport->graphviz_images = $graphvizImg;
                        $this->cfgExport->gif_images = $gifImg;
                        $this->cfgExport->figure_references= $figRef;
                        $this->cfgExport->table_references= $tabRef;
                    }
                }
                else if ($item["valueType"] == "arrayFields") {
                    $typedefKeyField = $this->getTypedefKeyField($item["value"]);
                    $renderKeyField = $this->getRenderKeyField($item["name"]);
                    $render = $this->createRender($typedefKeyField, $renderKeyField);
                    $render->init($item["name"], $renderKeyField['render']['styletype']);

                    $arrayDataField = $this->getDataField($item["value"]);
                    if (!is_array($arrayDataField)) {
                        $arrayDataField = json_decode($arrayDataField, true);
                    }
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
        $_SESSION['figure_references'] = &$this->cfgExport->figure_references;
        $_SESSION['table_references'] = &$this->cfgExport->table_references;
        $_SESSION['alternateAddress'] = TRUE;
        $_SESSION['dir_images'] = "img/";
        if ($this->cfgExport->styletype){
            $_SESSION['styletype'] = $this->cfgExport->styletype;
        }

        if (preg_match("/".$this->cfgExport->id."/", $data)!=1){
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
        try {
            $html = $this->render($instructions, $renderData);
        }catch (Exception $e) {
            throw new Exception($e->getMessage().". En el document: $data");
        }
        if (empty($alias)){
            $alias = $data;
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
        if (isset($ret['arrayDocuments'])) {
            $ret = $this->preCocinadoIndividual($ret);
        }else {
            $ret = $this->cocinandoLaPlantillaConDatos($ret);
        }
        return $ret;
    }
    
    /**
     * Tractament específic per a la generació de fitxers, individuals, resultat de cocinandoLaPlantillaConDatos
     * @param array $data : dades ja renderitzades. La renderització del contigut de cada document està individualitzada
     *                      en $data['arrayDocuments']
     * @return array
     */
    public function preCocinadoIndividual($data) {
        $id = str_replace(':', '_', $this->cfgExport->id);
        $toc_backup = $this->cfgExport->toc;
        
        $latexImg_backup = $this->cfgExport->latex_images;
        $mediaFiles_backup = $this->cfgExport->media_files;
        $graphvizImg_backup = $this->cfgExport->graphviz_images;
        $gifImg_backup = $this->cfgExport->gif_images;
        $figRef_backup = $this->cfgExport->figure_references;
        $tabRef_backup = $this->cfgExport->table_references;

        
        foreach ($data['arrayDocuments'] as $doc => $arrayDocuments) { //para cada documento
            $this->cfgExport->toc = [];
            foreach ($arrayDocuments as $name => $value) { //para cada tipo: pdf, html
                $data[$name] = $value;
                $this->cfgExport->toc[$name] = $toc_backup[$doc];
            }
            $this->cfgExport->output_filename = "{$id}_{$doc}";

            $this->cfgExport->latex_images = $latexImg_backup[$doc];
            $this->cfgExport->media_files = $mediaFiles_backup[$doc];
            $this->cfgExport->graphviz_images = $graphvizImg_backup[$doc];
            $this->cfgExport->gif_images = $gifImg_backup[$doc];
            $this->cfgExport->figure_references = $figRef_backup[$doc];
            $this->cfgExport->table_references = $tabRef_backup[$doc];
            
            $result[$this->cfgExport->output_filename] = $this->cocinandoLaPlantillaConDatos($data);
        }

        $this->cfgExport->toc = $toc_backup ;        
        $this->cfgExport->latex_images = $latexImg_backup ;
        $this->cfgExport->media_files = $mediaFiles_backup ;
        $this->cfgExport->graphviz_images = $graphvizImg_backup ;
        $this->cfgExport->gif_images = $gifImg_backup ;
        $this->cfgExport->figure_references = $figRef_backup ;
        $this->cfgExport->table_references = $tabRef_backup ;

        $ret['tmp_dir'] = $this->cfgExport->tmp_dir;
        foreach ($result as $value) {
            if ($value['error']) {
                $ret['error'][] = $value['error'];
            }else {
                $ret['files'][] = $value['file'];
                $ret['fileNames'][] = $value['fileName'];
            }
            $ret['info'][] = $value['info'];
        }
        return $ret;
    }

    public function cocinandoLaPlantillaConDatos($data) {
        if (is_array($data)){
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
    
    function addDefaultCssFilesToZip(&$zip, $rdir) {
        $this->addFilesToZip($zip, realpath(__DIR__)."/xhtml", $rdir, "css", TRUE);
    }
    
    protected function addFilesToZip(&$zip, $base, $d, $dir, $recursive=FALSE, $file=FALSE) {
        $zip->addEmptyDir("$d$dir");
        $files = $this->getDirFiles("$base/$dir");
        foreach($files as $f) {
            if (!$file || basename($f) === $file) {
                $zip->addFile($f, "$d$dir/".basename($f));
            }
        }
        if ($recursive) {
            $dirs = $this->getDirs("$base/$dir");
            foreach($dirs as $dd){
                $this->addFilesToZip($zip, "$base/$dir", "$d$dir/", basename($dd));
            }
        }
    }    

    /**
     * Genera un JSON a partir de un template WIOCCL y los datos del proyecto
     * @param array $data : Datos del proyecto
     * @param type $file : ruta del fichero template
     * @return JSON
     */
    protected function replaceInJsonTemplate($data, $file) {
        $tmplt = $this->loadTemplateFile($file);
        $document = WiocclParser::getValue($tmplt, [], $data);
        return trim($document, " \n,");
    }
    
    protected function getDirFiles($dir){
        $files = array();
        if (file_exists($dir) && is_dir($dir) && is_readable($dir)) {
            $dh = opendir($dir);
            while ($file = readdir($dh)) {
                if ($file != '.' && $file != '..' && !is_dir("$dir/$file")) {
                    if (preg_match('/.*?\.pdf|.*?\.png|.*?\.jpg|.*?\.gif|.*?\.ico|.*?\.css|.*?\.js|.*?\.htm|.*?\.html|.*?\.svg/', $file)){
                        array_push($files, "$dir/$file");
                    }
                }
            }
            closedir($dh);
        }
        return $files;
    }    
    
    protected function getDirs($dir){
        $files = array();
        if (file_exists($dir) && is_dir($dir) && is_readable($dir)) {
            $dh = opendir($dir);
            while ($file = readdir($dh)) {
                if ($file != '.' && $file != '..' && is_dir("$dir/$file")) {
                    array_push($files, "$dir/$file");
                }
            }
            closedir($dh);
        }
        return $files;
    }    
}

