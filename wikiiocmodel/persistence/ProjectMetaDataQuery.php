<?php
/**
 * Description of ProjectMetaDataQuery
 * @author josep et al.
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC."lib/plugins/");
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_PLUGIN."wikiiocmodel/");

require_once (DOKU_INC."inc/JSON.php");

class ProjectMetaDataQuery extends DataQuery {

    const KEY_STATE            = "state";
    const PATH_METADATA_CONFIG = "metadata/config/";
    const FILE_CONFIGMAIN      = "configMain.json";
    const FILE_DEFAULTVIEW     = "defaultView.json";
    const DEFAULT_PROJECT_TYPE_DIR = WIKI_IOC_MODEL . "projects/defaultProject/";

    const LOG_TYPE_CREATE     = 'C';
    const LOG_TYPE_EDIT       = 'E';
    const LOG_TYPE_MINOR_EDIT = 'e';
    const LOG_TYPE_DELETE     = 'D';
    const LOG_TYPE_REVERT     = 'R';

    private $projectId = NULL;          //ID del projecte actual
    private $projectSubset = FALSE;     //subSet actual del projecte
    private $projectType = FALSE;       //tipus de projecte
    private $projectFileName = FALSE;   //Nom de l'arxiu de dades corresponent a aquest tipus de projecte
    private $projectTypeDir = FALSE;    //Ruta completa al directori del tipus de projecte
    private $revision = FALSE;          //Data de l'arxiu de revisió
    private $actual_revision = FALSE;   //Indica si es volen obtenir les dades de la versió actual del projecte

    public function __construct($projectId=FALSE, $projectSubset=FALSE, $projectType=FALSE, $revision=FALSE) {
        if($projectId || $projectSubset || $projectType){
            $this->init($projectId, $projectSubset, $projectType);
        }
        $this->revision = $revision;
    }

    public function init($projectId, $projectSubset=FALSE, $projectType=FALSE, $revision=FALSE){
        $this->projectId = $projectId;
        $this->projectSubset = $projectSubset;
        $this->projectType = $projectType;
        $this->revision = $revision;
        $this->projectFileName = FALSE;
        $this->projectTypeDir = FALSE;
        return $this;
    }

    public function setProjectId($projectId){
        $this->projectId = $projectId;
    }

    public function setRevision($rev){
        $this->revision = $rev;
    }

    public function setProjectType($projectType){
        $this->projectType = $projectType;
    }

    public function setProjectSubset($projectSubset){
        $this->projectSubset = $projectSubset;
    }

    public function setActualRevision($actual_revision){
        $this->actual_revision = $actual_revision;
    }

    public function getRevision(){
        return ($this->getActualRevision()) ? NULL : $this->revision;
    }

    public function getProjectId(){
        return $this->projectId;
    }

    public function getProjectSubset(){
        if (!$this->projectSubset){
            $this->setProjectSubset(AjaxKeys::VAL_DEFAULTSUBSET);
        }
        return $this->projectSubset;
    }

    public function getActualRevision(){
        return $this->actual_revision;
    }

    public function getProjectType($external_id=NULL) {
        $ret = $this->projectType;
        if (!$this->projectType || $external_id) {
            //obtenir el projectType del directori
            $id = ($external_id===NULL) ? $this->getProjectId() : $external_id;
            $dir = WikiGlobalConfig::getConf('mdprojects')."/".str_replace(":", "/", $id)."/";
            $ext = WikiGlobalConfig::getConf('mdextension');
            $dirList = scandir($dir) ;
            $found = false;
            for ($i=0; !$found && $i<count($dirList); $i++){
                if (is_dir($dir.$dirList[$i])){
                    if (preg_grep("/.*\.$ext/", scandir($dir.$dirList[$i]))){
                        $ret = $dirList[$i];
                        if ($external_id===NULL) {
                            $this->projectType = $dirList[$i];
                        }
                        $found = true;
                    }
                }
            }
        }
        return  $ret;
    }

    public function getListMetaDataComponentTypes($configMainKey, $component) {
        //lista de elementos permitidos para el componente dado
        $jsonList = $this->getMetaDataConfig($configMainKey);
        if (!empty($jsonList)) {
            $arrayList = json_decode($jsonList, true);
            return $arrayList[$this->getProjectSubset()][$component];
        }else {
            return NULL;
        }
    }

    /**
     * Devuelve la lista ordenada de tipos de proyecto obtenida a partir de la lectura
     * de la estructura de directorios de 'plugin'/projects/
     */
    public function getListProjectTypes($all=FALSE) {
        global $plugin_controller;
        if (!$all) {
            $listProjects = $this->getListMetaDataComponentTypes(ProjectKeys::KEY_METADATA_COMPONENT_TYPES,
                                                                 ProjectKeys::KEY_MD_CT_SUBPROJECTS);
        }
        $plugin_list = $plugin_controller->getList('action');
        foreach ($plugin_list as $plugin) {
            $pluginProjectsDir = DOKU_PLUGIN."$plugin/projects/";
            if (($projectsDir = @opendir($pluginProjectsDir))) {
                while ($pType = readdir($projectsDir)) {
                    if (is_dir($pluginProjectsDir.$pType) && $pType !== '.' && $pType !== '..') {
                        if ($listProjects) {
                            if (in_array($pType, $listProjects)) {
                                $ret[] = $pType;
                            }
                        }else {
                            $ret[] = $pType;
                        }
                    }
                }
            }
        }
        if ($ret) sort($ret);
        return $ret;
    }

    /**
     * Devuelve un array [name,path] de plantillas, obtenido a partir de la lectura
     * del directorio de plantillas indicado
     */
    public function getListTemplateDirFiles($nsDirTemplates) {
        $dirTemplates = WikiGlobalConfig::getConf('datadir'). "/" . str_replace(":", "/", $nsDirTemplates). "/";
        if (($list = @opendir($dirTemplates))) {
            while ($template = readdir($list)) {
                if ($template !== '.' && $template !== '..') {
                    $template = preg_replace("/.txt$/", "", $template);
                    if (is_dir($dirTemplates.$template)) {
                        $ret = $this->getListTemplateDirFiles("$nsDirTemplates:$template");
                    }else {
                        $ret[] = ['name' => $template, 'path' => "$nsDirTemplates:$template"];
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Devuelve una lista ordenada con los tipos de proyecto contenidos en el plugin
     */
    public function getPluginProjectTypes($plugin) {
        $pluginProjectsDir = DOKU_PLUGIN."$plugin/projects/";
        if (($projectsDir = @opendir($pluginProjectsDir))) {
            while ($pType = readdir($projectsDir)) {
                if (is_dir($pluginProjectsDir.$pType) && $pType !== '.' && $pType !== '..') {
                    $ret[] = $pType;
                }
            }
        }
        if ($ret) sort($ret);
        return $ret;
    }

    /**
     * AHORA MISMO NO LA USA NADIE
     * Devuelve un array de tipos de proyecto contenidos en el plugin
     */
    public function getArrayProjectTypes($plugin) {
        $pluginProjectsDir = DOKU_PLUGIN."$plugin/projects/";
        if (($projectsDir = @opendir($pluginProjectsDir))) {
            while ($pType = readdir($projectsDir)) {
                if (is_dir($pluginProjectsDir.$pType) && $pType !== '.' && $pType !== '..') {
                    $ret[] = ['project' => $pType,
                              'dir' => $pluginProjectsDir.$pType."/"];
                }
            }
        }
        return $ret;
    }

    /**
     * Devuelve un array de tipos de proyecto obtenido a partir de la lectura
     * de la estructura de directorios de 'plugin'/projects/
     */
    public function getAllArrayProjectTypes() {
        global $plugin_controller;
        $plugin_list = $plugin_controller->getList('action');
        foreach ($plugin_list as $plugin) {
            $pluginProjectsDir = DOKU_PLUGIN."$plugin/projects/";
            if (($projectsDir = @opendir($pluginProjectsDir))) {
                while ($pType = readdir($projectsDir)) {
                    if (is_dir($pluginProjectsDir.$pType) && $pType !== '.' && $pType !== '..') {
                        $ret[] = ['plugin' => $plugin,
                                  'project' => $pType,
                                  'dir' => $pluginProjectsDir.$pType."/"];
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Busca si la ruta (ns) es un proyecto
     * @param string $ns
     * @return boolean
     */
    public function existProject() {
        return $this->isAProject($this->getProjectId());
    }

    /**
     * Obtiene el array correspondiente a la clave $configMainKey del subSet actual del archivo FILE_CONFIGMAIN
     * @param string $configMainKey : conjunto principal requerido
     * @param string $projectType
     * @return Json con el array correspondiente a la clave $configMainKey del subSet actual del archivo FILE_CONFIGMAIN
     */
    public function getMetaDataConfig($configMainKey, $projectType=FALSE) {
        if (!$projectType){
            $projectType = $this->getProjectType();
        }
        $projectTypeDir = $this->getProjectTypeDir($projectType);
        $path = $projectTypeDir . self::PATH_METADATA_CONFIG . self::FILE_CONFIGMAIN;
        $configMain = @file_get_contents($path);
        if ($configMain == false) {
            $configMain = @file_get_contents(self::DEFAULT_PROJECT_TYPE_DIR . self::PATH_METADATA_CONFIG . self::FILE_CONFIGMAIN);
        }

        $subset = $this->getProjectSubset();
        $configArray = json_decode($configMain, true);

        if ($configArray[$configMainKey]) {
            for ($i = 0; $i < sizeof($configArray[$configMainKey]); $i++) {
                if (isset($configArray[$configMainKey][$i][$subset])) {
                    $toReturn = json_encode($configArray[$configMainKey][$i]);
                    break;
                }
            }
        }
        return $toReturn;
    }

    /**
     * Obtiene un array con el contenido del archivo de control (formato json) especificado
     * @param string $projectType
     * @param string $jsonFile : fichero json requerido
     * @param string $configKey : conjunto principal requerido
     * @return Json con el array correspondiente a la clave $configKey
     */
    public function getMetaDataJsonFile($projectType=FALSE, $jsonFile=self::FILE_CONFIGMAIN, $configKey=NULL) {
        if (!$projectType){
            $projectType = $this->getProjectType();
        }
        $projectTypeDir = $this->getProjectTypeDir($projectType);
        $path = $projectTypeDir . self::PATH_METADATA_CONFIG . $jsonFile;
        $config = @file_get_contents($path);
        if ($config == false) {
            $config = @file_get_contents(self::DEFAULT_PROJECT_TYPE_DIR . self::PATH_METADATA_CONFIG . $jsonFile);
        }
        $configArray = json_decode($config, true);
        return ($configKey) ? $configArray[$configKey] : $configArray;
    }

    //["""overwrite"""] copia de MetaDataDaoConfig.php
    //Devuelve un array con el contenido, del subset actual, de la clave principal especificada del archivo configMain.json
    private function getMetaDataDefinition($configMainKey=NULL, $projectType=FALSE) {
        if ($configMainKey === NULL) {
            $configMainKey = ProjectKeys::KEY_METADATA_PROJECT_STRUCTURE;
        }
        $jsonConfigProject = $this->getMetaDataConfig($configMainKey, $projectType);
        $arrConfigProject = $this->controlMalFormedJson($jsonConfigProject, "array");
        return $arrConfigProject;
    }

    //[TRASPASO] Viene de MetaDataDaoConfig.php
    //Retorna el contenido de la subclave $metaDataubset de la clave 'metaDataComponentTypes' del configMain.json
    public function getMetaDataComponentTypes($metaDataSubSet, $projectType=FALSE) {
        $ret = $this->getMetaDataDefinition(ProjectKeys::KEY_METADATA_COMPONENT_TYPES, $projectType, $metaDataSubSet);
        return ($ret) ? $ret[$metaDataSubSet] : NULL;
    }

    //["""overwrite"""] copia de MetaDataDaoConfig.php
    public function getMetaDataDefKeys() {
        $ret = $this->getMetaDataDefinition(ProjectKeys::KEY_METADATA_PROJECT_STRUCTURE);
        $type = $ret['mainType']['typeDef'];
        return json_encode($ret['typesDefinition'][$type]['keys']);
    }

    //["""overwrite""" (más bien suplantación de nombre, dado que son distintas] copia de MetaDataDaoConfig.php
    public function getMetaDataStructure() {
        return $this->getMetaDataDefinition(ProjectKeys::KEY_METADATA_PROJECT_STRUCTURE);
    }

    //Retorna el contenido de la subclave $metaDataSubset de la clave 'metaDataFtpSender' del configMain.json
    public function getMetaDataFtpSender($key=FALSE, $metaDataSubSet=FALSE) {
        $ret = $this->getMetaDataDefinition(ProjectKeys::KEY_METADATA_FTP_SENDER);
        if (!$metaDataSubSet) $metaDataSubSet = $this->getProjectSubset();
        return ($ret) ? ($key) ? $ret[$metaDataSubSet][$key] : $ret[$metaDataSubSet] : NULL;
    }

    /*
     * Obtiene el atributo solicitado de la clave principal solicidada del archivo configMain.json
     */
    public function getMetaDataAnyAttr($attr=NULL, $configMainKey=NULL) {
        $configMainKey = ($configMainKey===NULL) ? ProjectKeys::KEY_METADATA_PROJECT_STRUCTURE : $configMainKey;
        $arrconfig = $this->getMetaDataDefinition($configMainKey, FALSE);
        return ($attr) ? $arrconfig[$attr] : $arrconfig;
    }

    /**
     * Obtiene un array con un conjunto de subSets, extraidos de la clave 'metaDataClassesNameSpaces', del archivo FILE_CONFIGMAIN
     * @param string $projectType
     * @return array con la lista de subSets del archivo FILE_CONFIGMAIN
     */
    public function getListMetaDataSubSets($projectType=FALSE) {
        $configSet = ProjectKeys::KEY_METADATA_CLASSES_NAMESPACES;
        if(!$projectType){
            $projectType = $this->getProjectType();
        }
        $projectTypeDir = $this->getProjectTypeDir($projectType);
        $path = $projectTypeDir . self::PATH_METADATA_CONFIG . self::FILE_CONFIGMAIN;
        $configMain = @file_get_contents($path);
        if ($configMain == false) {
            $configMain = @file_get_contents(self::DEFAULT_PROJECT_TYPE_DIR . self::PATH_METADATA_CONFIG . self::FILE_CONFIGMAIN);
        }

        $configArray = json_decode($configMain, true);
        $toReturn = array();

        for ($i = 0; $i < sizeof($configArray[$configSet]); $i++) {
            $toReturn[] = array_keys($configArray[$configSet][$i])[0];
        }
        return $toReturn;
    }

    /**
     * Extrae el conjunto de campos definidos en la configuración de datos del tipo de proyecto
     * Se usa cuando todavía no hay datos en el fichero de proyecto, entonces se recoge la lista de campos del tipo de proyecto
     * @return JSON conteniendo el conjunto de campos del subset
     */
    public function getStructureMetaDataConfig() {
        $metaStructure = $this->getMetaDataConfig(ProjectKeys::KEY_METADATA_PROJECT_STRUCTURE);

        if ($metaStructure) {
            $content = json_decode($metaStructure, TRUE);
            $typeDef = $content['mainType']['typeDef'];
            $keys = $content['typesDefinition'][$typeDef]['keys'];

            foreach ($keys as $k => $v) {
                $metaData[$k] = ($v['default']) ? $v['default'] : "";
            }
            $metaDataReturn = json_encode($metaData);
        }
        return $metaDataReturn;
    }

    public function getMetaViewConfig($viewConfig, $projectType=FALSE) {
        if(!$projectType){
            $projectType = $this->getProjectType();
        }
        $projectTypeDir = $this->getProjectTypeDir($projectType);
        $view = @file_get_contents($projectTypeDir . self::PATH_METADATA_CONFIG . "$viewConfig.json");
        if ($view == false) {
            $view = @file_get_contents($projectTypeDir . self::PATH_METADATA_CONFIG . self::FILE_DEFAULTVIEW);
            if ($view == false) {
                $view = @file_get_contents(self::DEFAULT_PROJECT_TYPE_DIR . self::PATH_METADATA_CONFIG . self::FILE_DEFAULTVIEW);
            }
        }
        $viewArray = json_decode($view, true);
        return $viewArray;
    }

    //Retorn JSON {ns1:projectType1, …, nsm:projectTypem} Obtiene un array con las propiedades del nodo y sus hijos de 1er nivel.
    public function getMetaDataElementsKey($nsRoot) {

        $elementsKeyArray = $this->getNsTree($nsRoot, 0, true, false, false, false);

        $returnArray = array();
        foreach ($elementsKeyArray['children'] as $arrayElement) {
            if ($arrayElement['type'] == 'p') {
                $returnArray[$arrayElement['id']] = $arrayElement[ProjectKeys::KEY_PROJECT_TYPE];
            }
        }

        // Add the $nsRoot itself, if it's a project (only a type of project)
        $metaDataPath = WikiGlobalConfig::getConf('mdprojects');
        $metaDataExtension = WikiGlobalConfig::getConf('mdextension');
        $pathProject = $metaDataPath . '/'. str_replace(':', '/', $nsRoot);
        $dirProject = @opendir($pathProject);

        if ($dirProject) {  //En el proceso de creación de un proyecto, no existe, todavía el directorio del proyecto
            while ($current = readdir($dirProject)) {
                $pathProjectOne = $pathProject . '/' . $current;
                if (is_dir($pathProjectOne)) {
                    $dirProjectOne = opendir($pathProjectOne);
                    while ($currentOne = readdir($dirProjectOne)) {
                        if (!is_dir($pathProjectOne . '/' . $currentOne)) {
                            $fileTokens = explode(".", $currentOne);
                            if ($fileTokens[sizeof($fileTokens) - 1] == $metaDataExtension) {
                                //És projecte i escriure   p
                                $returnArray[$nsRoot] = $current;
                            }
                        }
                    }
                }
            }
        }

        if (sizeof($returnArray) > 0) {
            $toReturn = json_encode($returnArray);
        }

        return $toReturn;
    }

    /**
     * Devuelve el estado de generación del proyecto
     * @return boolean : true si el proyecto ya ha sido generado
     */
    public function isProjectGenerated() {
        return $this->getProjectSystemStateAttr("generated");
    }

    public function getProjectSystemStateAttr($att) {
        $data = $this->getSystemData(self::KEY_STATE);
        return $data[$att];
    }

    public function getProjectSystemSubSetAttr($att, $subset=FALSE) {
        $data = $this->getSystemData($subset);
        return $data[$att];
    }

    /**
     * Establece el estado 'generated'=true del proyecto
     * @return boolean : true si el estado del proyecto se ha establecido con éxito
     */
    public function setProjectGenerated() {
        return $this->setProjectSystemStateAttr("generated", TRUE);
    }

    /**
     * Establece el atributo con el valor especificado en la clave KEY_STATE del archivo de sistema de un proyecto
     * @return boolean : true si el atributo se ha establecido con éxito
     */
    public function setProjectSystemStateAttr($att, $value) {
        $jsSystem = $this->getSystemData(self::KEY_STATE);
        $jsSystem[$att] = $value;
        $success = $this->setSystemData($jsSystem, self::KEY_STATE);
        return $success;
    }

    /**
     * Establece el atributo con el valor especificado en la clave $subset del archivo de sistema de un proyecto
     * @return boolean : true si el atributo se ha establecido con éxito
     */
    public function setProjectSystemSubSetAttr($att, $value, $subset=FALSE) {
        $jsSystem = $this->getSystemData($subset);
        $jsSystem[$att] = $value;
        return $this->setSystemData($jsSystem, $subset);
    }

    /**
     * Establece el atributo con el valor especificado en la clave version del subset del archivo de sistema de un proyecto
     * @return boolean : true si el atributo se ha establecido con éxito
     */
    public function setProjectSystemSubSetVersion($att, $value, $subset=FALSE) {
        $jsSystem = $this->getSystemData($subset);
        if ($att === "fields")
            $jsSystem['versions'][$att] = $value;
        else
            $jsSystem['versions']['templates'][$att] = $value;
        $success = $this->setSystemData($jsSystem, $subset);
        return $success;
    }

    /**
     * Extrae, del contenido del fichero, los datos correspondientes a la clave
     * @param string $subSet : clave del contenido
     * @param string $revision : fecha unix de la revisión
     */
    public function getMeta($subSet=FALSE, $revision=FALSE) {
        if (!$subSet) $subSet = $this->getProjectSubset();
        $file = $this->getFileName($this->getProjectId(),
                                   [ProjectKeys::KEY_REV => $revision,
                                    ProjectKeys::KEY_PROJECT_TYPE => $this->getProjectType(),
                                    ProjectKeys::KEY_METADATA_SUBSET => $subSet]);
        return $this->_getMeta($subSet, $file);
    }
    /**
     * Extrae, del contenido del fichero, los datos correspondientes a la clave
     * @param string $subSet : clave del contenido
     * @param string $filename : fichero de datos del proyecto / ruta completa para las revisiones
     * @return JSON conteniendo el array de la clave 'metadatasubset' con los datos del proyecto
     */
    private function _getMeta($subSet, $filename) {
        $metaDataReturn = null;
        $contentFile = io_readFile($filename, false);

        if ($contentFile != false) {
            $contentMainArray = $this->controlMalFormedJson($contentFile, "array");
            foreach ($contentMainArray as $clave => $valor) {
                if ($clave == $subSet) {
                    if (is_array($valor)) {
                        $metaDataReturn = json_encode($valor);
                        break;
                    }
                }
            }
        }
        return $metaDataReturn;
    }

    /**
     * Guarda el nuevo archivo de datos del proyecto, guardando previamente la versión anterior como una revisión
     * @param JSON   $metaDataValue   Nou contingut de l'arxiu de dades del projecte
     * @param string $metaDataSubSet
     * @param string $summary
     * @param JSON   $upgrade
     * @param boolean $revision Debe indicarse FALSE cuando lo llama el proceso RevertProject
     * @return string
     */
    public function setMeta($metaDataValue, $metaDataSubSet=FALSE, $summary="", $upgrade="", $revision=NULL) {
        if (!$metaDataSubSet){
            $metaDataSubSet = $this->getProjectSubset();
        }
        if ($upgrade==="") {
            global $plugin_controller;
            $upgrade = '{"fields":"'.$plugin_controller->getCurrentProjectVersions("fields").'"}' ;
        }
        $projectType = $this->getProjectType();
        return $this->_setMeta($metaDataSubSet,
                               $this->getProjectFilePath($this->getProjectId(), $projectType, $revision),
                               $this->getProjectFileName($metaDataSubSet, $projectType, $revision),
                               $metaDataValue,
                               $summary,
                               $upgrade);
    }

    /**
     * Guarda el nuevo archivo de datos del proyecto, guardando previamente la versión anterior como una revisión
     * @param string $metaDataSubSet  Valor de metadatasubset (exemple: "main")
     * @param string $projectFileName Nom de l'arxiu de dades del projecte (exemple: "meta.mdpr")
     * @param JSON   $metaDataValue   Nou contingut de l'arxiu de dades del projecte
     * @param string $summary
     * @param JSON   $upgrade
     * @return string
     */
    private function _setMeta($metaDataSubSet, $projectFilePath, $projectFileName, $metaDataValue, $summary="", $upgrade="") {
        $projectFilePathName = $projectFilePath . $projectFileName;
        $projectId = $this->getProjectId();

        if (!is_file($projectFilePathName)) {
            //Entramos aquí cuando se trata de la primera modificación de los datos del subset
            $resourceCreated = $this->_createResource($projectFilePath, $projectFileName);
            if ($resourceCreated) {
                $resourceCreated = $this->_setSystemData($projectId, $projectFilePath);
            }
            if (!$resourceCreated) {
                return '{"error":"5090"}';
            }
        }else {
            $prev_date = filemtime($projectFilePathName);
        }

        $contentFile = $this->_cleanContent("{\"$metaDataSubSet\":$metaDataValue}");
        $resourceCreated = io_saveFile($projectFilePathName, $contentFile);
        if ($resourceCreated) {
            $new_date = filemtime($projectFilePathName);
            if (!$prev_date) $prev_date = $new_date;
            if (!empty($upgrade)) $extra['extra'] = $upgrade;
            $this->_saveRevision($prev_date, $new_date, $projectId, $projectFileName, $contentFile, $summary, $extra);
        }
        return $resourceCreated;
    }

    /**
     * Limpia una cadena de texto de caracteres indeseables a partir de una lista
     * @param string $content - Texto que se desea limpiar
     * @return string - Texto limpio
     */
    private function _cleanContent($content) {
        $aSearch = ["\\r\\n", "\\ufeff"];
        $aReplace = ["\n", " "];
        return str_replace($aSearch, $aReplace, $content);
    }

    private function _createResource($dirProject, $file) {
        $resourceCreated = is_dir($dirProject);
        if (!$resourceCreated) {
            //Crea, si no existe, la estructura de directorios en 'mdprojects'
            $resourceCreated = mkdir($dirProject, 0777, true);
        }
        if ($resourceCreated) {
            // Crea y verifica el fichero .mdpr que contendrá los datos del proyecto
            $fp = @fopen("$dirProject/$file", 'w');
            if (($resourceCreated = ($fp !== false))) {
                fclose($fp);
            }
        }
        return $resourceCreated;
    }

    public function getSystemData($metaDataSubSet=FALSE) {
        if (!$metaDataSubSet){
            $metaDataSubSet = $this->getProjectSubset();
        }
        $dirProject = $this->getProjectFilePath(NULL, NULL, FALSE);
        $file = WikiGlobalConfig::getConf('projects','wikiiocmodel')['dataSystem'];
        if (is_file($dirProject.$file)) {
            $systemContent = json_decode(file_get_contents($dirProject.$file), true);
            return $systemContent[$metaDataSubSet];
        }
        return NULL;
    }

      //Obté el contingut d'una platilla situada en el directori del projecte/metadata/plantilles
    public function getRawProjectTemplate($filename, $version=FALSE) {
        if($version){
            $extension = ".txt.v$version";
        }else{
            $extension = ".txt";
        }
        $dir = $this->getProjectTypeDir();
        $content = io_readFile("{$dir}metadata/plantilles/$filename$extension");
        return $content;
    }

    public function setSystemData($data, $metaDataSubSet=FALSE) {
        if (!$metaDataSubSet){
            $metaDataSubSet = $this->getProjectSubset();
        }
        $dirProject = $this->getProjectFilePath(NULL, NULL, FALSE);
        $file = WikiGlobalConfig::getConf('projects','wikiiocmodel')['dataSystem'];
        $systemContent = json_decode(file_get_contents($dirProject.$file), true);
        $systemContent[$metaDataSubSet] = $data;
        $succes = io_saveFile("$dirProject$file", json_encode($systemContent));
        return $succes;
    }
    /**
     * Crea el archivo de sistema del proyecto y guarda datos de estado
     * @param string $id (ruta ns del proyecto)
     * @param string $dirProject
     * @return boolean : indica si la creación del fichero ha tenido éxito
     */
    private function _setSystemData($id, $dirProject) {
        //Crea el fichero de sistema del proyecto
        $parentProject = $this->getThisProject($id)['nsproject'];
        $state = ['generated' => false];
        if ($parentProject && $parentProject !== $id) {
            $state['parentNs'] = $parentProject;
        }
        $data['state'] = $state;
        $file = WikiGlobalConfig::getConf('projects','wikiiocmodel')['dataSystem'];
        $succes = io_saveFile("$dirProject$file", json_encode($data));
        return $succes;
    }

    /**
     * Devuelve la ruta completa al fichero del proyecto (en mdprojects)
     * @param string $id : wikiRuta de la página del proyecto
     * @param array $params : {projectType, metaDataSubSet, revision}
     * @return string
     */
    public function getFileName($id, $params=array()) {
        $revision = (isset($params[ProjectKeys::KEY_REV])) ? $params[ProjectKeys::KEY_REV] : $this->getRevision();
        $projectType = (isset($params[ProjectKeys::KEY_PROJECT_TYPE])) ? $params[ProjectKeys::KEY_PROJECT_TYPE] : $this->getProjectType();
        $metaDataSubSet = (isset($params[ProjectKeys::KEY_METADATA_SUBSET])) ? $params[ProjectKeys::KEY_METADATA_SUBSET] : $this->getProjectSubset();
        $path = $this->getProjectFilePath($id, $projectType, $revision);
        $file = $this->getProjectFileName($metaDataSubSet, $projectType, $revision);
        return $path . $file;
    }

    /**
     * Devuelve el nombre del archivo de datos para este tipo de proyecto
     */
    public function getProjectFileName($metaDataSubSet=FALSE, $projectType=FALSE, $revision=NULL) {
        if ($metaDataSubSet || !$this->projectFileName) {
            if (!$projectType){
                $projectType = $this->getProjectType();
            }
            $struct = $this->getMetaDataDefinition(ProjectKeys::KEY_METADATA_PROJECT_STRUCTURE, $projectType);
            $this->projectFileName = $struct[$metaDataSubSet];
        }
        if ($revision===NULL){
            $revision = $this->getRevision();
        }
        if (!$metaDataSubSet){
            $metaDataSubSet = $this->getProjectSubset();
        }
        $ret = $this->projectFileName;
        if ($revision){
            $ret = "$ret.$revision.txt.gz";
        }
        return $ret;
    }

    private function getProjectFilePath($id=NULL, $projectType=NULL, $revision=NULL) {
        if ($id===NULL){
            $id = $this->getProjectId();
        }
        $id = utf8_encodeFN(str_replace(":", "/", $id));
        if ($projectType===NULL){
            $projectType = $this->getProjectType();
        }
        if ($revision===NULL){
            $revision = $this->getRevision();
        }
        if ($revision){
            $path = WikiGlobalConfig::getConf('revisionprojectdir') . "/$id/";
        }else{
            $path = WikiGlobalConfig::getConf('mdprojects') . "/$id/$projectType/";
        }
        return $path;
    }

    private function updateProjectTypeDir($projectType=FALSE) {
            global $plugin_controller;
            if(!$projectType){
                $projectType = $this->getProjectType();
            }
            $this->projectTypeDir = $plugin_controller->getProjectTypeDir($projectType);
    }

    public function getProjectTypeDir($projectType=FALSE){
        if(!$this->projectTypeDir){
            $this->updateProjectTypeDir($projectType);
        }
        return $this->projectTypeDir;
    }

    public function getNsTree($currentNode, $sortBy, $onlyDirs=FALSE, $expandProjects=TRUE, $hiddenProjects=FALSE, $root=FALSE) {
        $base = WikiGlobalConfig::getConf('datadir');
        return $this->getNsTreeFromGenericSearch($base, $currentNode, $sortBy, $onlyDirs, 'search_universal', $expandProjects, $hiddenProjects, $root);
    }

    public function createDataDir($id) {
        $id = str_replace(":", "/", $id);
        $dir = WikiGlobalConfig::getConf('datadir') . "/" . utf8_encodeFN($id) . "/dummy";
        $this->makeFileDir($dir);
    }

    /**
     * Canvia el contingut dels arxius de dreceres d'autors i responsables amb la nova ruta del projecte
     * @param string $old_name : nom actual del projecte
     * @param string $new_name : nou nom del projecte
     * @param string $persons : noms dels autors i els responsables separats per ","
     * @throws Exception
     */
    public function changeOldPathProjectInShortcutFiles($old_name, $new_name, $persons) {
        $path_dreceres = WikiGlobalConfig::getConf('datadir') . str_replace(":", "/", WikiGlobalConfig::getConf('userpage_ns','wikiiocmodel'));
        $nom_dreceres = WikiGlobalConfig::getConf('shortcut_page_name','wikiiocmodel') . ".txt";
        $persons = explode(",", $persons);
        foreach ($persons as $user) {
            $file = "$path_dreceres$user/$nom_dreceres";
            if (@file_exists($file)) {
                if (($content = file_get_contents($file))) {
                    $content = preg_replace("/$old_name/", "$new_name", $content);
                    if (file_put_contents($file, $content, LOCK_EX) === FALSE)
                        throw new Exception("renameProject: Error mentre canviava el contingut de la drecera de $user.");
                }
            }
        }
    }

    /**
     * Canvia el nom dels arxius que contenen (en el nom) la ruta del projecte original (del qual es fa la duplicació)
     * @param string $base_dir : directori wiki del projecte duplicat
     * @param string $old_path : directori wiki del projecte original
     * @param string $old_name : nom actual del projecte original
     * @param string $new_name : nou nom del projecte duplicat (nom actual)
     * @param array|string $listfiles : llista d'arxius o extensió dels arxius (per defecte ".zip") generats pel render que cal renombrar
     * @throws Exception
     */
    public function renameDuplicateGeneratedFiles($base_dir, $new_name, $old_path, $old_name, $listfiles=["extension","\.zip"], $recursive=FALSE) {
        $newPath = WikiGlobalConfig::getConf('mediadir')."/$base_dir/$new_name";
        $old_name = str_replace("/", "_", "$old_path/$old_name");
        $new_name = str_replace("/", "_", "$base_dir/$new_name");
        $ret = $this->_renameDuplicateGeneratedFiles($newPath, $old_name, $new_name, $listfiles, $recursive);
        if (is_string($ret)) {
            throw new Exception("duplicateProject: Error mentre canviava el nom de l'arxiu $ret.");
        }
    }

    /**
     * Canvia el nom dels arxius que contenen (en el nom) la ruta del projecte original (del qual es fa la duplicació)
     * @param string $path : ruta sencera del sistema al directori 'data/media' de la wiki
     * @param string $old_name : nom del projecte original
     * @param string $new_name : nou nom del projecte duplicat
     * @param array $listfiles : llista de terminacions dels arxius
     * @return boolean|string TRUE si ha ido bien, "ruta del fichero" si se ha producido error al renombrar
     */
    private function _renameDuplicateGeneratedFiles($path, $old_name, $new_name, $listfiles, $recursive=FALSE) {
        $ret = TRUE;
        $scan = @scandir($path);
        if ($scan) $scan = array_diff($scan, [".", ".."]);
        if ($scan) {
            foreach ($scan as $file) {
                if (is_dir("$path/$file")) {
                    if ($recursive) {
                        $ret = $this->_renameRenderGeneratedFiles("$path/$file", $old_name, $new_name, $listfiles, TRUE);
                        if (is_string($ret)) break;
                    }
                }elseif (preg_match("/^$old_name/", $file)) {
                    if (!empty($listfiles)) {
                        for ($i=1; $i<count($listfiles); $i++) {
                            $ext .= $listfiles[$i] ."|";
                        }
                        $ext = substr($ext, 0, -1);
                        if ($listfiles[0] === "fullname") {
                            $search = "/($ext)/";
                        }else {
                            $search = "/{$old_name}($ext)/";
                        }
                        if (preg_match($search, $file)) {
                            $newfile = preg_replace("/{$old_name}([\.|_])/", "{$new_name}$1", $file);
                            $ret = rename("$path/$file", "$path/$newfile");
                            if (!$ret) {
                                $ret = "$path/$file";
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Canvia el contingut dels arxius ".changes" i ".meta" que contenen la ruta del directori per la nova ruta
     * @param string $base_dir : directori wiki del projecte duplicat
     * @param string $new_name : nou nom del projecte duplicat
     * @param string $old_path : base del projecte original
     * @param string $old_name : nom del projecte original
     * @throws Exception
     */
    public function changeOldPathInDuplicateRevisionFiles($base_dir, $new_name, $old_path, $old_name, $file_sufix=[], $recursive=FALSE) {
        $paths = ['metadir',       /*meta*/
                  'mediametadir',  /*media_meta*/
                  'metaprojectdir' /*project_meta*/
                 ];
        if (empty($file_sufix)) {
            $suffix = FALSE;
        }else {
            array_pop($file_sufix);
            $suffix = "(".implode("|", $file_sufix).")";
        }
        $newName = str_replace("/", ":", $base_dir).":".$new_name;
        $oldName = str_replace("/", ":", $old_path).":".$old_name;
        $list_files = "\.(changes|meta)";
        $ret = TRUE;
        foreach ($paths as $dir) {
            $newPath = WikiGlobalConfig::getConf($dir)."/$base_dir/$new_name";
            $ret = $this->_changeOldPathInDuplicateFiles($newPath, $newName, $oldName, $list_files, $suffix, $recursive);
            if (is_string($ret)) break;
        }
        if (is_string($ret)) {
            throw new Exception("duplicateProject: Error mentre canviava el contingut de $ret.");
        }
    }

    /**
     * Canvia el contingut dels arxius que contenen l'antiga ruta del projecte (normalment la ruta absoluta a les imatges)
     * @param string $base_dir : directori wiki del projecte duplicat
     * @param string $new_name : nou nom del projecte duplicat
     * @param string $old_path : base del projecte original
     * @param string $old_name : nom del projecte original
     * @throws Exception
     */
    public function changeOldPathInDuplicateContentFiles($base_dir, $new_name, $old_path, $old_name, $file_sufix=FALSE, $recursive=FALSE) {
        $newPath = WikiGlobalConfig::getConf('datadir')."/$base_dir/$new_name";
        $suffix = (is_array($file_sufix)) ? "(".implode("|", $file_sufix).")" : FALSE;
        $newName = str_replace("/", ":", $base_dir).":".$new_name;
        $oldName = str_replace("/", ":", $old_path).":".$old_name;
        $ret = $this->_changeOldPathInDuplicateFiles($newPath, $newName, $oldName, "\.txt$", $suffix, $recursive);
        if (is_string($ret)) {
            throw new Exception("duplicateProject: Error mentre canviava el contingut d'algun axiu a $ret.");
        }
    }

    private function _changeOldPathInDuplicateFiles($path, $newName, $oldName, $list_files, $suffix=FALSE, $recursive=FALSE) {
        $ret = TRUE;
        $scan = @scandir($path);
        $scan = ($scan) ? array_diff($scan, [".", ".."]) : NULL;
        if ($scan) {
            foreach ($scan as $file) {
                if (is_dir("$path/$file")) {
                    if ($recursive) {
                        $ret = $this->_changeOldPathInDuplicateFiles("$path/$file", $newName, $oldName, $list_files, $suffix, TRUE);
                        if (is_string($ret)) break;
                    }
                }elseif (preg_match("/$list_files/", $file)) {
                    if (($content = file_get_contents("$path/$file"))) {
                        $c = $c1 = $c2 = 0;
                        $content = preg_replace("/\b$oldName((:|\t|\"))?/m", "{$newName}$1", $content, -1, $c);
                        $nName = str_replace(":", "_", $newName);
                        $oName = str_replace(":", "_", $oldName);
                        $content = preg_replace("/\b$oName((:|\t|\"))?/m", "{$nName}$1", $content, -1, $c1);
                        $c += $c1;
                        if ($suffix) {
                            if (preg_match("/{$oldName}/", $content)) {
                                $content = preg_replace("/($oldName)(_*?.*?)($suffix)/", "{$newName}$2$3", $content, -1, $c2);
                                $c += $c2;
                            }elseif (preg_match("/{$oName}/", $content)) {
                                $content = preg_replace("/($oName)(_*?.*?)($suffix)/", "{$nName}$2$3", $content, -1, $c2);
                                $c += $c2;
                            }
                        }
                        if ($c > 0) {
                            $ret = file_put_contents("$path/$file", $content, LOCK_EX);
                            if (!$ret) {
                                $ret = "$path/$file";
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Canvia el contingut dels arxius de dreceres d'autors i responsables amb la nova ruta del projecte
     * @param string $old_name : nom actual del projecte
     * @param string $new_name : nou nom del projecte
     * @param string $persons : noms dels autors i els responsables separats per ","
     * @throws Exception
     */
    public function duplicateOldPathProjectInShortcutFiles($old_name, $new_name, $persons) {
        $path_dreceres = WikiGlobalConfig::getConf('datadir') . str_replace(":", "/", WikiGlobalConfig::getConf('userpage_ns','wikiiocmodel'));
        $nom_dreceres = WikiGlobalConfig::getConf('shortcut_page_name','wikiiocmodel') . ".txt";
        $persons = explode(",", $persons);
        foreach ($persons as $user) {
            $file = "$path_dreceres$user/$nom_dreceres";
            if (@file_exists($file)) {
                if (($content = file_get_contents($file))) {
                    $search = "/^\[\[{$old_name}.*\]\]$/m";
                    if (preg_match($search, $content, $stmp) === 1) {
                        $insert = $stmp[0]."\n\n[[{$new_name}|accés al projecte {$new_name}]]";
                        $content = preg_replace($search, $insert, $content);
                    }
                    if (file_put_contents($file, $content, LOCK_EX) === FALSE)
                        throw new Exception("duplicateProject: Error mentre canviava el contingut de la drecera de $user.");
                }
            }
        }
    }

    /**
     * Duplica les entrades de l'arxiu ACL que contenen la ruta antiga del projecte amb la nova ruta
     * @param string $old_name : nom actual del projecte
     * @param string $new_name : nou nom del projecte
     * @throws Exception
     */
    public function duplicateOldPathInACLFile($old_dir, $old_name, $new_dir, $new_name) {
        $file = DOKU_CONF."acl.auth.php";
        if (($content = file_get_contents($file))) {
            $old_dir = str_replace("/", ":", $old_dir);
            $new_dir = str_replace("/", ":", $new_dir);
            $search = "/^({$old_dir}:{$old_name})(:\*\s*\b.*\s*[0-9])$/m";
            if (preg_match($search, $content, $stmp) === 1) {
                $insert = $stmp[1]."$2"."\n{$new_dir}:{$new_name}$2";
                $content = preg_replace($search, $insert, $content);
            }
            if (file_put_contents($file, $content, LOCK_EX) === FALSE)
                throw new Exception("duplicateProject: Error mentre canviava el nom del projecte/directori a $file.");
        }
    }

    /**
     * Elimina els directoris del projecte indicat i les seves referències i enllaços
     * @param string $ns : ns del projecte
     * @param string $persons : noms dels autors i els responsables separats per ","
     */
    public function removeProject($ns, $persons) {
        //1. Elimina els directoris relacionats amb el projecte indicat
        $aFolders = $this->_arrayDataFolders();
        $project_dir = str_replace(":","/", $ns);

        foreach ($aFolders as $folder) {
            $full_dir = WikiGlobalConfig::getConf($folder)."/$project_dir";
            if (!$this->_removeDir($full_dir)) {
                throw new Exception("removeProject: Error mentre eliminava el directori $folder/$project_dir del projecte.");
            }
        }

        //2. Canvia el contingut de l'arxiu ACL que pot contenir la ruta del projecte
        $file = DOKU_CONF."acl.auth.php";
        $content = file_get_contents($file);
        $content = preg_replace("/^.*:*$ns:\*.*\d$/m", "", $content);
        if (file_put_contents($file, $content, LOCK_EX) === FALSE)
            throw new Exception("removeProject: Error mentre eliminava el nom del projecte a $file.");

        //3. Canvia el contingut dels arxius de dreceres de autors i responsables eliminant la ruta del projecte
        $path_dreceres = WikiGlobalConfig::getConf('datadir') . str_replace(":", "/", WikiGlobalConfig::getConf('userpage_ns','wikiiocmodel'));
        $nom_dreceres = WikiGlobalConfig::getConf('shortcut_page_name','wikiiocmodel') . ".txt";
        $persons = explode(",", $persons);
        foreach ($persons as $user) {
            $file = "$path_dreceres$user/$nom_dreceres";
            if(file_exists($file)){
                $content = file_get_contents($file);
                $content = preg_replace("/^\[\[.*:*$ns\W.*\]\]$/m", "", $content);
                if (file_put_contents($file, $content, LOCK_EX) === FALSE)
                    throw new Exception("removeProject: Error mentre eliminava el nom del projecte de la drecera de $user.");
            }
        }
    }

    /**
     * Elimina un directorio, eliminando primero sus hijos
     * @param string $dir : directorio que se desea eliminat
     * @return boolean : TRUE si ha conseguido eliminar el directorio (incluye sus hijos)
     */
    private function _removeDir($dir) {
        $ret = TRUE;
        $scan = @scandir($dir);
        if ($scan) $scan = array_diff($scan, [".", ".."]);
        if ($scan) {
            foreach ($scan as $file) {
                if (is_dir("$dir/$file")) {
                    if (!($ret = $this->_removeDir("$dir/$file"))) break;
                }else {
                    if (!($ret = $ret && unlink("$dir/$file"))) break;
                }
            }
        }
        if ($ret && is_dir($dir)) {
            $ret = rmdir($dir);
        }
        return $ret;
    }

    /**
     * @return array Con los datos del proyecto (.mdpr en mdprojects/) correspondientes a la clave '$metaDataSubSet'
     */
    public function getDataProject($id=FALSE, $projectType=FALSE, $metaDataSubSet=FALSE) {
        if (!$id)
            $id = $this->getProjectId();
        if (!$projectType)
            $projectType = $this->getProjectType();
        if (!$metaDataSubSet)
            $metaDataSubSet = $this->getProjectSubset();

        $filename = $this->getFileName($id, [ProjectKeys::KEY_PROJECT_TYPE=>$projectType, ProjectKeys::KEY_METADATA_SUBSET=>$metaDataSubSet]);
        $jsonData = $this->_getMeta($metaDataSubSet, $filename);
        if ($jsonData!==NULL) {
            return json_decode($jsonData, TRUE);
        }else {
            return NULL;
        }
    }

    public function hasDataProject($id=FALSE, $projectType=FALSE, $metaDataSubSet=FALSE) {
        $data = $this->getDataProject($id, $projectType, $metaDataSubSet);
        if ($data!==NULL) {
            return TRUE;
        }else {
            return NULL;
        }
    }

    /**
     * Informa si un proyecto tiene establecida a la actualización de proyecto (clave metaDataProjectConfig de configMain)
     * @return boolean : TRUE indica que el proyecto tiene establecida la clave de actualización
     */
    public function hasTypeConfigFile($projectType=FALSE, $metaDataSubSet=FALSE) {
        if (!$projectType)
            $projectType = $this->getProjectType();
        if (!$metaDataSubSet)
            $metaDataSubSet = $this->getProjectSubset();
        $jsonConfigProject = $this->getMetaDataConfig(ProjectKeys::KEY_METADATA_PROJECT_CONFIG, $projectType);
        $arrConfigProject = $this->controlMalFormedJson($jsonConfigProject, "array");
        return !empty($arrConfigProject[$metaDataSubSet][ProjectKeys::KEY_MD_PROJECTTYPECONFIGFILE]);
    }

    private function _saveRevision($prev_date, $new_date, $projectId, $projectFileName, $old_content, $summary="", $flags=[]) {
        $resourceCreated = FALSE;
        $new_rev_file = $this->getProjectFilePath($projectId, NULL, $new_date) . "$projectFileName.$new_date.txt";
        $resourceCreated = io_saveFile("$new_rev_file.gz", $old_content);

        $last_rev_date = key($this->getProjectRevisionList(1));
        if ($last_rev_date && $last_rev_date < $prev_date) {
            $summary = WikiIocLangManager::getLang('external_edit') . ". $summary";
            $flags['ExternalEdit'] = TRUE;
        }
        $resourceCreated &= $this->_addProjectLogEntry($new_date, $projectId, self::LOG_TYPE_EDIT, $summary, $flags);
        return ($resourceCreated) ? $new_date : "";
    }

    /**
     * Logs del proceso de guardar una modificación del archivo de datos del proyecto.
     * @param string $mdate     fecha de última modificación del archivo de datos del proyecto
     * @param string $projectId ruta relativa del proyecto
     * @param string $type      tipo de modificación
     * @param string $summary
     * @param array $flags
     * @return boolean
     */
    private function _addProjectLogEntry($mdate, $projectId, $type=self::LOG_TYPE_EDIT, $summary="", $flags=[]) {
        $strip = array("\t", "\n");
        if (!empty($flags)) {
            $flagExternalEdit = isset($flags['ExternalEdit']);
            $extra = isset($flags['extra']) ? $flags['extra'] : "";
        }
        $record = array(
            'date'  => $mdate,
            'ip'    => (!$flagExternalEdit) ? clientIP(true) : "127.0.0.1",
            'type'  => str_replace($strip, "", $type),
            'id'    => str_replace("/", ":", $projectId),
            'user'  => (!$flagExternalEdit) ? $_SERVER['REMOTE_USER'] : "",
            'sum'   => utf8_substr(str_replace($strip, "", $summary), 0, 255),
            'extra' => ($extra!==NULL) ? $extra : ""
            );

        //meta log
        $ret = $this->_addLogMetaFile($projectId, $record );

        //changes log
        $ret &= $this->_addLogChangesFile($projectId, $record);

        return $ret;
    }

    /**
     * En este log se guarda una línea por cada modificación sufrida por el archivo de datos del proyecto
     * @param string $projectId ruta relativa del proyecto
     * @param array  $record    datos del registro de log
     * @return boolean
     */
    private function _addLogChangesFile($projectId, $record) {
        $ret = TRUE;
        $record_line = implode("\t", $record)."\n";
        $ch_filename = $this->_metaProjectFN($projectId, "", ".changes");

        $fh = @fopen($ch_filename, "r");
        if ($fh) {
            $fh2 = @fopen("$ch_filename.tmp", "w");
            $bytes = fwrite($fh2, $record_line);
            while (!feof($fh)) {
                fwrite($fh2, fgets($fh));
            }
            fclose($fh2);
            fclose($fh);
            $ret &= rename("$ch_filename.tmp", $ch_filename);
        }else {
            $fh = @fopen($ch_filename, "w");
            $bytes = fwrite($fh, $record_line);
            fclose($fh);
        }
        $ret &= ($bytes === strlen($record_line));
        return $ret;
    }

    /**
     * Log del proceso de guardar una modificación del archivo de datos del proyecto. Es el log que se guarda en
     * el archivo projectId/projectFilename.meta. Este archivo log contiene un JSON de metadatos del proyecto
     * @param string $projectId ruta relativa del proyecto
     * @param array  $record    datos del registro de log
     * @return boolean
     */
    private function _addLogMetaFile($projectId, $record) {
        $projectFilePath = ($this->projectFilePath) ? $this->projectFilePath : $this->getProjectFilePath($projectId, NULL, FALSE);
        $projectFilePathName = $projectFilePath . $this->projectFileName;
        $minor = ($record['type'] === self::LOG_TYPE_MINOR_EDIT);
        $user   = $record['user'];
        $created = @filectime($projectFilePathName);

        $old_meta = $this->p_read_projectmetadata($projectId);
        $new_meta = array();
        if (!WikiIocInfoManager::getInfo('exists')) {
            if (empty($old_meta['persistent']['date']['created'])) { //newly created
                $new_meta['date']['created'] = $created;
                if ($user){
                    $new_meta['creator'] = WikiIocInfoManager::getInfo('userinfo')['name'];
                    $new_meta['user']    = $user;
                }
            } elseif (!empty($old_meta['persistent']['date']['created'])) { //re-created / restored
                $new_meta['date']['created']  = $old_meta['persistent']['date']['created'];
                $new_meta['date']['modified'] = $created; // use the files ctime here
                $new_meta['creator'] = $old_meta['persistent']['creator'];
                if ($user) $new_meta['contributor'][$user] = WikiIocInfoManager::getInfo('userinfo')['name'];
            }
        } elseif (!$minor) {
            $new_meta['date']['modified'] = $record['date'];
            if ($user) $new_meta['contributor'][$user] = WikiIocInfoManager::getInfo('userinfo')['name'];
        }
        $new_meta['last_change'] = $record;
        $ret = $this->p_set_projectmetadata($projectId, $new_meta);
        return $ret;
    }

    private function _metaProjectFN($projectId, $filename="", $ext="") {
        $projectId = utf8_encodeFN(str_replace(":", "/", $projectId));
        if ($filename==="") {
            $filename = $this->getProjectFileName(FALSE, FALSE, FALSE); //Siempre ignora la existencia de $revision
        }
        $file = WikiGlobalConfig::getConf('metaprojectdir') . "/$projectId/$filename$ext";
        return $file;
    }

    private function p_set_projectmetadata($projectId, $data){
        if (!is_array($data))
            return false;

        $meta = $orig = $this->p_read_projectmetadata($projectId);
        $protected = array('description', 'date', 'contributor');

        foreach ($data as $key => $value){
            if ($key == 'relation'){
                foreach ($value as $subkey => $subvalue){
                    if (isset($meta['current'][$key][$subkey]) && is_array($meta['current'][$key][$subkey])) {
                        $meta['current'][$key][$subkey] = array_merge($meta['current'][$key][$subkey], (array)$subvalue);
                    }else {
                        $meta['current'][$key][$subkey] = $subvalue;
                    }

                    if (isset($meta['persistent'][$key][$subkey]) && is_array($meta['persistent'][$key][$subkey])) {
                        $meta['persistent'][$key][$subkey] = array_merge($meta['persistent'][$key][$subkey], (array)$subvalue);
                    }else {
                        $meta['persistent'][$key][$subkey] = $subvalue;
                    }
                }
            }elseif (in_array($key, $protected)){
                // these keys, must have subkeys - a legitimate value must be an array
                if (is_array($value)) {
                    $meta['current'][$key] = !empty($meta['current'][$key]) ? array_merge((array)$meta['current'][$key],$value) : $value;
                    $meta['persistent'][$key] = !empty($meta['persistent'][$key]) ? array_merge((array)$meta['persistent'][$key],$value) : $value;
                }
            }else {
                $meta['current'][$key] = $value;
                $meta['persistent'][$key] = $value;
            }
        }

        // save only if metadata changed
        if ($meta == $orig)
            return true;
        else
            return $this->p_save_projectmetadata($projectId, $meta);
    }

    private function p_read_projectmetadata($idProject, $filename="") {
        $meta_file = $this->_metaProjectFN($idProject, $filename, ".meta");
        if (@file_exists($meta_file))
            $meta = unserialize(io_readFile($meta_file, false));
        else
            $meta = array('current' => array(), 'persistent' => array());
        return $meta;
    }

    private function p_save_projectmetadata($idProject, $meta) {
        return io_saveFile($this->_metaProjectFN($idProject, "", ".meta"), serialize($meta));
    }

    /**
     * Retorna un array con las líneas del archivo de log .changes
     * @param string $projectId
     * @param int    $num        Número de registros solicitados
     * @param int    $chunk_size Máximo número de bytes que van a leerse del fichero de log
     * @return array
     */
    public function getProjectRevisionList($num=1, $chunk_size=1024) {
        $revs = array();
        $actrev = $this->getActualRevision(); //¿A QUE MOLA MUCHO?
        $this->setActualRevision(TRUE);
        $file = $this->_metaProjectFN($this->getProjectId(), "", ".changes");
        $this->setActualRevision($actrev);

        if (@file_exists($file)) {
            if (filesize($file) < $chunk_size || $num==0 || $chunk_size==0) {
                $lines = file($file);
                if ($num==0 || $chunk_size==0) $num = count($lines);
            }else {
                $fh = fopen($file, 'r');
                if ($fh) {
                    $lines[] = fgets($fh, $chunk_size);
                    $count = floor($chunk_size / strlen($lines[0]));
                    $i = 1;
                    while (!feof($fh) && $i < $count) {
                        $lines[] = fgets($fh);
                        $i++;
                    }
                    fclose($fh);
                }
            }
            for ($i=0; $i<$num; $i++) {
                if (!empty(trim($lines[$i]))) {
                    $registre = explode("\t", $lines[$i]);
                    $revs[$registre[0]]['date'] = date("d-m-Y h:i:s", $registre[0]);
                    $revs[$registre[0]]['ip']   = $registre[1];
                    $revs[$registre[0]]['type'] = $registre[2];
                    $revs[$registre[0]]['id']   = $registre[3];
                    $revs[$registre[0]]['user'] = $registre[4];
                    $revs[$registre[0]]['sum']  = trim($registre[5]);
                    $revs[$registre[0]]['extra']= trim($registre[6]);
                }
            }
        }
        return $revs;
    }

    public function getLastModFileDate() {
        $fn = $this->getFileName($this->getProjectId());
        if (@file_exists($fn)) {
            return filemtime($fn);
        }
    }

    public function controlMalFormedJson($jsonVar, $typeReturn="object") {
        if ($jsonVar) {
            $t = ($typeReturn==="array") ? TRUE : FALSE;
            $obj = json_decode($jsonVar, $t);
            if (json_last_error() != JSON_ERROR_NONE) {
                throw new MalFormedJSON();
            }
        }
        return $obj;
    }

    /*AFEGIR UNA FUNCIÖ PER REANOMENAR FITXERS QUE PERTANYIN A UNA PROJECTE
     Amb una signatura semblant a:*/
    //$nsParcial = ns realtiu al ns del projecte
    //$mabit = es refereix al lloc on es troba el fitxer (data, media, attic, etc.)
    public function renameFile($nsParcial, $ambit="data"){
        throw new UnavailableMethodExecutionException("renameFile");
    }
}
