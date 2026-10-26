<?php
/**
 * BasicFactoryExporter
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();

require_once dirname(__FILE__)."/BasicExporterClasses.php";

class BasicFactoryExporter {
    protected $mode;     //modelo de comando que genera la acción [html | pdf]
    protected $filetype; //tipo de fichero que se desea generar [zip | pdf]
    protected $path;     //ruta absoluta del directorio exporter del proyecto
    protected $typesDefinition = array();
    protected $typesRender = array();
    protected $projectModel = NULL;
    protected $defaultValueForObjectFields = "";

    public function __construct($path) {
        $this->path = $path;
    }

    /**
     * @param array $typesDefinition : array con todos los tipos (clave 'typesDefinition') del archivo configMain.json
     * @param array $typesRender : array con todos los tipos del archivo configRender.json
     */
    public function init($params=array()) {
        $this->mode            = $params['mode'];
        $this->filetype        = $params['filetype'];
        $this->typesDefinition = $params['typesDefinition'];
        $this->typesRender     = $params['typesRender'];
        $this->projectModel    = $params['projectModel'];
        $this->defaultValueForObjectFields = $params['defaultValueForObjectFields'];
    }

    /**
     * @param array $typedef : tipo (objeto en configMain.json) correspondiente al campo actual en $data
     * @param array $renderdef : tipo (objeto en configRender.json) correspondiente al campo actual en $data
     * @return instancia del render correspondiente
     */
    public function createRender($typedef=NULL, $renderdef=NULL, $params=NULL) {
        $class = $this->getKeyRenderClass($renderdef);

        require_once $this->path."/exporterClasses.php";
        require_once $this->path."/".$this->mode."/exporterClasses.php";
        if ($class && @file_exists($this->path."/".$this->mode."/".$class."/exporterClasses.php")){
            require_once $this->path."/".$this->mode."/".$class."/exporterClasses.php";
        }
        $class = $this->validateClass($class, $typedef['type']);
        
       //creamos una instancia del render correspontiente al tipo de elemento
        switch ($typedef['type']) {
            case "array":  $render = new $class($this, $typedef, $renderdef, $params); break;
            case "object": $render = new $class($this, $typedef, $renderdef, $params); break;
            case "file":   $render = new $class($this, $params); break;
            case "date":   $render = new $class($this, $params, "/"); break;
            default:       $render = new $class($this, $params); break;
        }
        return $render;
    }

    public function getMode() {
        return $this->mode;
    }

    public function getFileType() {
        return $this->filetype;
    }

    public function getProjectModel() {
        return $this->projectModel;
    }

    public function getDefaultValueForObjectFields() {
        return $this->defaultValueForObjectFields;
    }

    public function getTypesDefinition($key = NULL) {
        return ($key === NULL) ? $this->typesDefinition : $this->typesDefinition[$key];
    }

    public function getTypesRender($key = NULL) {
        return ($key === NULL) ? $this->typesRender : $this->typesRender[$key];
    }

    public function getDocumentClass() {
        return $this->getKeyRenderClass($this->getTypesRender('document'));
    }
    
    public function getPathExporterProject(){
        return $this->path;
    }

    private function validateClass($class, $tipo) {
        if ($class === NULL || !class_exists($class, TRUE)) {
            $class = $this->defaultRenderClass($tipo); //render por defecto del tipo definido en configMain.json
        }
        return $class;
    }

    private function getKeyRenderClass($renderdef) { //devuelve el nombre de la clase render
        return $renderdef['render']['class'];
    }

    /**
     * Establece la clase por defecto para cada tipo
     * @param string $tipo : tipo de objeto (string, array, object, number, etc.)
     * @return string : nombre de la clase asignada por defecto a ese tipo
     */
    private function defaultRenderClass($tipo) {
        switch ($tipo) {
            case 'array':  $ret = "renderArray"; break;
            case 'object': $ret = "renderObject"; break;
            case 'date':   $ret = "renderDate"; break;
            case 'string': $ret = "renderText";break;
            default:       $ret = "renderField"; break;
        }
        return $ret;
    }
}

