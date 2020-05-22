<?php
/**
 * AbstractProjectModel
 * @author professor
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . "lib/plugins/");
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_PLUGIN . "wikiiocmodel/");

require_once (DOKU_INC . "inc/common.php");
require_once (WIKI_IOC_MODEL . "metadata/MetaDataService.php");

abstract class AbstractProjectModel extends AbstractWikiDataModel{
    protected $id;
    protected $rev;
    protected $projectType;
    protected $metaDataSubSet;

    //protected $persistenceEngine; Ya está definida en AbstractWikiModel
    protected $metaDataService;
    protected $draftDataQuery;
    protected $lockDataQuery;
    protected $dokuPageModel;
    protected $viewConfigName;

    public function __construct($persistenceEngine)  {
        parent::__construct($persistenceEngine);
        $this->metaDataService= new MetaDataService();
        $this->draftDataQuery = $persistenceEngine->createDraftDataQuery();
        $this->lockDataQuery = $persistenceEngine->createLockDataQuery();
        $this->dokuPageModel = new DokuPageModel($persistenceEngine);
        $this->viewConfigName = "defaultView";
    }

    public function getId(){
        return $this->id;
    }
    
    public function getDokuPageModel(){
        return $this->dokuPageModel;
    }

    public function init($params, $projectType=NULL, $rev=NULL, $viewConfigName="defaultView", $metadataSubset=Projectkeys::VAL_DEFAULTSUBSET) {
        if(is_array($params)){
            $this->id          = $params[ProjectKeys::KEY_ID];
            $this->projectType = $params[ProjectKeys::KEY_PROJECT_TYPE];
            $this->rev         = $params[ProjectKeys::KEY_REV];
            $this->metaDataSubSet = ($params[ProjectKeys::KEY_METADATA_SUBSET]) ? $params[ProjectKeys::KEY_METADATA_SUBSET] : ProjectKeys::VAL_DEFAULTSUBSET;
            if ($params[ProjectKeys::VIEW_CONFIG_NAME]){
                $this->viewConfigName = $params[ProjectKeys::VIEW_CONFIG_NAME];
            }
        }else{
            $this->id = $params;
            $this->projectType = $projectType;
            $this->rev = $rev;
            $this->metaDataSubSet = $metadataSubset;
            $this->viewConfigName=empty($viewConfigName)?"defaultView":$viewConfigName;
        }
        $this->projectMetaDataQuery->init($this->id);
        if($this->projectType){
            $this->projectMetaDataQuery->setProjectType($this->projectType);
        }
        if($this->metaDataSubSet){
            $this->projectMetaDataQuery->setProjectSubset($this->metaDataSubSet);
        }
        if($this->rev){
            $this->projectMetaDataQuery->setRevision($this->rev);
        }
    }

    public function getModelAttributes($key=NULL){
        $attr[ProjectKeys::KEY_ID] = $this->id;
        $attr[ProjectKeys::KEY_PROJECT_TYPE] = $this->getProjectType();
        $attr[ProjectKeys::KEY_REV] = $this->rev;
        $attr[ProjectKeys::KEY_METADATA_SUBSET] = $this->getMetaDataSubSet();
        return ($key) ? $attr[$key] : $attr;
    }

    public function getContentDocumentId($docId){
        if(is_array($docId)){
            return $this->getContentDocumentIdFromResponse($docId);
        }
        return $this->id.":".$docId;
    }

    protected function getContentDocumentIdFromResponse($responseData){
//        Cal fer abstracta aquesta funció
    }


    public function setActualRevision($actual_revision){
        $this->projectMetaDataQuery->setActualRevision($actual_revision);
    }

    public function getActualRevision(){
        return $this->projectMetaDataQuery->getActualRevision();
    }

    public function getMetaDataSubSet() {
        return ($this->metaDataSubSet) ? $this->metaDataSubSet : ProjectKeys::VAL_DEFAULTSUBSET;
    }

    public function isAlternateSubSet() {
        return ($this->metaDataSubSet && $this->metaDataSubSet !== ProjectKeys::VAL_DEFAULTSUBSET);
    }

    public function setProjectId($projectId) {
        $this->id = $projectId;
        $this->projectMetaDataQuery->setProjectId($projectId);
    }

    /**
     * Retorna el sufijo para el ID de la pestaña de un proyecto para un subset distinto de 'main' o una revisión
     * @params string $rev . Si existe, indica que es una revisión del proyecto
     * @return string
     */
    public function getIdSuffix($rev=FALSE) {
        $ret = "";
        if ($this->isAlternateSubSet()){
            $ret .= "-".$this->getMetaDataSubSet();
        }
        if ($rev) {
            $ret .= ProjectKeys::REVISION_SUFFIX;
        }
        return $ret;
    }

    //Obtiene el contenido de un archivo wiki, es decir, está en pages/$id:nombre y tienen extensión .txt
    public function getRawProjectDocument($filename) {
        $content = $this->getPageDataQuery()->getRaw("{$this->id}:$filename");
        return $content;
    }

    public function getRawTemplate($filename, $version) {
        $content = $this->getPageDataQuery()->getTemplateRaw($filename, $version);
        return $content;
    }

    /**
     * Obtiene el contenido del archivo wiki indicado en $filename. Está en pages/$filename con extensión .txt
     * @param string $filename : ruta wiki (con :) del archivo (a partir de pages/)
     * @return string : contenido del archivo
     */
    public function getRawDocument($filename) {
        $content = $this->getPageDataQuery()->getRaw($filename);
        return $content;
    }

    /**
     * Obtiene una estructura de datos relativa al fichero indicado en la ruta $ns
     * @param string $ns : wiki-ruta del fichero solicitado
     * @return array : estructura de datos relativa al fichero (incluye su contenido en formato HTML)
     */
    public function getDataDocument($ns) {
        $this->dokuPageModel->init($ns);
        $data = $this->dokuPageModel->getData();
        return $data;
    }

    public function setRawProjectDocument($filename, $text, $summary) {
        $toSet = [ProjectKeys::KEY_ID => "{$this->id}:$filename",
                  PageKeys::KEY_WIKITEXT => $text,
                  PageKeys::KEY_SUM => $summary];
        $this->dokuPageModel->setData($toSet);
    }

    /*
     * Obtiene las listas de 'old persons'. Debe usarse antes de guardar los nuevos datos
     */
    public function getOldPersonsDataProject($id=FALSE, $projectType=FALSE, $metaDataSubSet=FALSE) {
        $oldDataProject = $this->getDataProject($id, $projectType, $metaDataSubSet);
        if (!empty($oldDataProject)) {
            return ['autor' => $oldDataProject['autor'],
                    'responsable' => $oldDataProject['responsable'],
                    'supervisor'=>$oldDataProject['supervisor']
                   ];
        }
    }

    /**
     * Obtiene los datos del proyecto o revisión del proyecto en uso, relativos
     * a la clave $metaDataSubset si se passa por paràmetro o a su valor por
     * defecto si no se pasa.
     */
    public function getCurrentDataProject($metaDataSubSet=FALSE) {
        return $this->getDataProject(FALSE, FALSE, $metaDataSubSet);
    }

    //Obtiene un array [key, value] con los datos del proyecto solicitado
    public function getDataProject($id=FALSE, $projectType=FALSE, $metaDataSubSet=FALSE) {
        //Actualitzar a aquí els camps calculats
        $ret =  $this->projectMetaDataQuery->getDataProject($id, $projectType, $metaDataSubSet);
        if ($ret) { //En el momento de la creación de proyecto $ret es NULL
            $ret = $this->processAutoFieldsOnRead($ret);
            $ret = $this->_updateCalculatedFieldsOnRead($ret);
        }
        return $ret;
    }

    public function hasDataProject($id=FALSE, $projectType=FALSE, $metaDataSubSet=FALSE){
       $ret =  $this->projectMetaDataQuery->hasDataProject($id, $projectType, $metaDataSubSet);
       return $ret;
    }

    /**
     * Obtiene y, después, retorna una estructura con los metadatos y valores del proyecto
     * @return array('projectMetaData'=>array('values','structure'), array('projectViewData'))
     */
    public function getData() {
        $ret = [];
        $subSet = $this->getMetaDataSubSet();
        $query = [
            ProjectKeys::KEY_PERSISTENCE => $this->persistenceEngine,
            ProjectKeys::KEY_PROJECT_TYPE => $this->getProjectType(),
            ProjectKeys::KEY_METADATA_SUBSET => $subSet,
            ProjectKeys::KEY_ID_RESOURCE => $this->id
        ];
        if ($this->rev) {
            $query[ProjectKeys::KEY_REV] = $this->rev;
        }
        $ret['projectMetaData'] = $this->metaDataService->getMeta($query, FALSE)[0];

        if ($this->viewConfigName === ProjectKeys::KEY_DEFAULTVIEW){  //CANVIAR $viewConfigName a VALOR NUMÊRIC
            $struct = $this->projectMetaDataQuery->getMetaDataStructure();
            if (!$ret['projectMetaData']) {
                //si todavía no hay datos en el fichero de proyecto se recoge la lista de campos del tipo de proyecto
                $typeDef = $struct['mainType']['typeDef'];
                $keys = $struct['typesDefinition'][$typeDef]['keys'];
                foreach ($keys as $k => $v) {
                    $metaData[$k] = ($v['default']) ? $v['default'] : "";
                }
                $ret['projectMetaData'] = $metaData;
            }
            if ($struct['viewfiles'][0]) {
                $this->viewConfigName = $struct['viewfiles'][0];
            }
        }
        $ret['projectViewData'] = $this->projectMetaDataQuery->getMetaViewConfig($this->viewConfigName);

        $ret['projectMetaData'] = $this->processAutoFieldsAndUpdateCalculatedFieldsOnReadFromStructuredData($ret['projectMetaData']);

        $this->mergeFieldConfig($ret['projectMetaData'], $ret['projectViewData']['fields']);
        $this->mergeFieldNameToLayout($ret['projectViewData']['fields']);

        return $ret;
    }

    /**
     * Construye un array de datos para la actualización de permisos (y shortcuts), sobre un proyecto,
     * de los usuarios (autores, responsables, etc) relacionados en el formulario del proyecto
     * @param array $newDataProject : array con los nuevos datos del proyecto
     * @param bool $old : indica si existen old_persons (no existen en el caso de CreateProject)
     * @return array con los datos necesarios
     */
    public function buildParamsToPersons($newDataProject, $oldDataProject=NULL) {
        $userpage_ns = preg_replace('/^:(.*)/', '\1', WikiGlobalConfig::getConf('userpage_ns','wikiiocmodel')); //elimina el ':' del principio

        $persons = [];
        if (!empty($oldDataProject['autor']) || !empty($newDataProject['autor']['value'])) {
            $persons['autor'] = ['old' => $oldDataProject['autor'],
                                 'new' => $newDataProject['autor']['value'],
                                 'permis' => AUTH_UPLOAD,
                                 'drecera' => TRUE];
        }
        if (!empty($oldDataProject['responsable']) || !empty($newDataProject['responsable']['value'])) {
            $persons['responsable'] = ['old' => $oldDataProject['responsable'],
                                       'new' => $newDataProject['responsable']['value'],
                                       'permis' => AUTH_UPLOAD,
                                       'drecera' => TRUE];
        }
        if (!empty($oldDataProject['supervisor']) || !empty($newDataProject['supervisor']['value'])) {
            $persons['supervisor'] = ['old' => $oldDataProject['supervisor'],
                                      'new' => $newDataProject['supervisor']['value'],
                                      'permis' => AUTH_READ,
                                      'drecera' => FALSE];
        }
        $params = [
             'id' => $this->id
            ,'link_page' => $this->id
            ,'persons' => $persons
            ,'userpage_ns' => $userpage_ns
            ,'shortcut_name' => WikiGlobalConfig::getConf('shortcut_page_name','wikiiocmodel')
        ];
        return $params;
    }

    /**
     * Elimina permisos ACL de old_person sobre la página del proyecto
     * @param string $old : username de la persona a la que se le quieren quitar los permisos
     * @param string $sNew : lista de usernames que tienen perimiso
     * @param string $project_ns : wiki ruta de la página del proyecto
     * @return array | NULL : lista de errores
     */
    private function _deleteACLPageToOldPerson($old, $sNew, $project_ns) {
        //lista de nuevas Persons
        $nPersons = array_unique(preg_split("/[\s,]+/", $sNew, NULL, PREG_SPLIT_NO_EMPTY));

        if (! in_array($old, $nPersons)) {
            //Elimina ACL de old_person sobre la página del proyecto
            $ret = PagePermissionManager::deletePermissionPageForUser($project_ns, $old);
            if (!$ret) $retError[] = "Error en eliminar permissos a '$old' sobre '$project_ns'";
        }
        return $retError;
    }

    /**
     * Elimina el enlace a la página del proyecto en el archivo dreceres de old_person
     * @param string $old : username al que se pretende eliminar el enlace
     * @param string $sNew : lista de las nuevas personas del proyecto (autores, responsables, ...)
     * @param bool   $drecera : indica si $old tiene permiso para crear dreceres
     * @param string $link_page : id de la página del proyecto
     * @param string $userpage_ns : wiki ruta base de las páginas de usuario
     * @param string $shortcut_name : nom de l'arxiu de dreceres
     */
    private function _removeShortcutPageToOldPerson($old, $sNew, $drecera, $link_page, $userpage_ns, $shortcut_name) {
        //lista de nuevas Persons
        $nPersons = array_unique(preg_split("/[\s,]+/", $sNew, NULL, PREG_SPLIT_NO_EMPTY));

        //Si old_peson tiene permiso para crear dreceres y no es new_person, elimina el enlace a la página del proyecto en su archivo dreceres
        if ($drecera && !in_array($old, $nPersons)) {
            $old_usershortcut = "$userpage_ns$old:$shortcut_name";
            $this->removeProjectPageFromUserShortcut($old_usershortcut, $link_page);
        }
    }

    /**
     * Añade un enlace a la página del proyecto en el archivo dreceres de new_person
     * @param string $new : username al que se añade el enlace
     * @param string $id : id de la página del proyecto
     * @param string $link_page : id de la página del proyecto
     * @param string $userpage_ns : wiki ruta base de las páginas de usuario
     * @param string $shortcut_name : nom de l'arxiu de dreceres
     */
    private function _addPageProjectToUserShortcut($new, $id, $link_page, $userpage_ns, $shortcut_name) {
        //Otorga permisos al autor sobre su propio directorio (en el caso de que no los tenga)
        $ns = "$userpage_ns$new:";
        PagePermissionManager::updatePagePermission($ns."*", $new, AUTH_DELETE, TRUE);

        //Escribe un enlace a la página del proyecto en el archivo de atajos de de new_person
        $params = [
             'id' => $id
            ,'link_page' => $link_page
            ,'user_shortcut' => $ns.$shortcut_name
        ];
        $this->includePageProjectToUserShortcut($params);
    }

    /**
     * Modifica los permisos en el fichero de ACL y la página de atajos del autor
     * cuando se modifica el autor o el responsable del proyecto
     * @param array $aParm ['id','link_page','persons[]','userpage_ns','shortcut_name']
     *                  'link_page' : id de la página del proyecto
     *                  'userpage_ns' : wiki ruta base de las páginas de usuario
     *                  'shortcut_name' : nom de l'arxiu de dreceres
     */
    public function modifyACLPageAndShortcutToPerson($aParm) {
        $project_ns = $aParm['id'].":*";

        foreach ($aParm['persons'] as $person) {
            $old_persons .= "{$person['old']},";
            $new_persons .= "{$person['new']},";
        }
        $oPersons = array_unique(preg_split("/[\s,]+/", $old_persons, NULL, PREG_SPLIT_NO_EMPTY));

        if (!empty($oPersons)) {
            foreach ($aParm['persons'] as $person) {
                $olds = preg_split("/[\s,]+/", $person['old'], NULL, PREG_SPLIT_NO_EMPTY);
                //Si se ha modificado una Person del proyecto (si existe algún old) ...
                foreach ($olds as $old) {
                    //elimina, si nada lo impide, los permisos de la antigua persona
                    $ret = $this->_deleteACLPageToOldPerson($old, $new_persons, $project_ns);
                    if ($ret) $retError[] = $ret;
                    //elimina, si nada lo impide, la entrada shortcut del archivo dreceres de la persona
                    $this->_removeShortcutPageToOldPerson($old, $new_persons, $person['drecera'], $aParm['link_page'], $aParm['userpage_ns'], $aParm['shortcut_name']);
                }
            }
        }

        //establece la auténtica lista de nuevas Persons
        foreach ($aParm['persons'] as $person) {
            $nPersons = array_unique(preg_split("/[\s,]+/", $person['new'], NULL, PREG_SPLIT_NO_EMPTY));
            $newPersons = array_diff($nPersons, $oPersons);
            foreach ($newPersons as $new) {
                //Crea ACL para new_person sobre la página del proyecto
                $ret = PagePermissionManager::updatePagePermission($project_ns, $new, $person['permis'], TRUE);
                if (!$ret) $retError[] = "Error en assignar permissos a '$new' sobre '$project_ns'";
                if ($person['drecera']) {
                    $this->_addPageProjectToUserShortcut($new, $aParm['id'], $aParm['link_page'], $aParm['userpage_ns'], $aParm['shortcut_name']);
                }
            }
        }

        if ($retError) {
            foreach ($retError as $e) {
                throw new UnknownProjectException($project_ns, $e);
            }
        }
    }

    /**
     * Inserta en la página de dreceres del usuario un texto con enlace al proyecto
     * Si la página dreceres.txt del usuario no existe, se crea a partir de la plantilla 'userpage_shortcuts_ns'
     * @param array $parArr ['id', 'link_page', 'user_shortcut']
     */
    protected function includePageProjectToUserShortcut($parArr) {
        $summary = "include Page Project To User Shortcut";
        $comment = ($parArr['link_page'] === $parArr['id']) ? "al" : "als continguts del";
        $shortcutText = "\n[[${parArr['link_page']}|accés $comment projecte ${parArr['id']}]]";
        $text = $this->getPageDataQuery()->getRaw($parArr['user_shortcut']);
        if ($text == "") {
            //La página dreceres.txt del usuario no existe
            $this->createPageFromTemplate($parArr['user_shortcut'], WikiGlobalConfig::getConf('template_shortcuts_ns', 'wikiiocmodel'), $shortcutText, $summary);
        }else {
            if (preg_match("/${parArr['link_page']}/", $text) === 1) {
                $eliminar = "/\[\[${parArr['link_page']}\|.*]]/";
                $text = preg_replace($eliminar, "", $text); //texto hallado -> eliminamos antiguo
            }
            $this->createPageFromTemplate($parArr['user_shortcut'], NULL, $text.$shortcutText, $summary);
        }
    }

    /**
     * Elimina el link al proyecto contenido en el archivo dreceres del usuario
     */
    private function removeProjectPageFromUserShortcut($usershortcut, $link_page) {
        $text = $this->getPageDataQuery()->getRaw($usershortcut);
        if ($text !== "" ) {
            if (preg_match("/$link_page/", $text) === 1) {  //subtexto hallado
                $eliminar = "/\[\[$link_page\|.*]]/";
                $text = preg_replace($eliminar, "", $text);
                $this->createPageFromTemplate($usershortcut, NULL, $text, "removeProjectPageFromUserShortcut");
            }
        }
    }

    /**
     * Canvia el nom dels directoris del projecte indicat,
     * els noms dels fitxers generats amb la base del nom del projecte i
     * les referències a l'antic nom de projecte dins dels fitxers afectats
     * @param string $ns : ns original del projecte
     * @param string $new_name : nou nom pel projecte
     * @param string $persons : noms dels autors i els responsables separats per ","
     */
    public function renameProject($ns, $new_name, $persons) {
        $base_dir = explode(":", $ns);
        $old_name = array_pop($base_dir);
        $base_dir = implode("/", $base_dir);

        $this->projectMetaDataQuery->renameDirNames($base_dir, $old_name, $new_name);
        $this->projectMetaDataQuery->changeOldPathInRevisionFiles($base_dir, $old_name, $new_name);
        $this->projectMetaDataQuery->changeOldPathInACLFile($old_name, $new_name);
        $this->projectMetaDataQuery->changeOldPathProjectInShortcutFiles($old_name, $new_name, $persons);
        $this->projectMetaDataQuery->renameRenderGeneratedFiles($base_dir, $old_name, $new_name, $this->listGeneratedFilesByRender($base_dir, $old_name));
        $this->projectMetaDataQuery->changeOldPathInContentFiles($base_dir, $old_name, $new_name);

        $new_ns = preg_replace("/:[^:]*$/", ":$new_name", $ns);
        $this->setProjectId($new_ns);;
    }

    /**
     * Elimina els directoris del projecte indicat i les seves referències i enllaços
     * @param string $ns : ns del projecte
     * @param string $persons : noms dels autors i els responsables separats per ","
     */
    public function removeProject($ns, $persons) {
        $this->projectMetaDataQuery->removeProject($ns, $persons);
    }

    /**
     * Crea el archivo $destino a partir de una plantilla
     */
    protected function createPageFromTemplate($destino, $plantilla=NULL, $extra=NULL, $summary="generate project") {
        $text = ($plantilla) ? $this->getPageDataQuery()->getRaw($plantilla) : "";
        $this->dokuPageModel->setData([PageKeys::KEY_ID => $destino,
                                       PageKeys::KEY_WIKITEXT => $text . $extra,
                                       PageKeys::KEY_SUM => $summary]);
    }

    protected function mergeFieldNameToLayout(&$projectViewDataFields) {
        // S'afegeix la informació dels fields al layout si no existeix
        // Per ara només cal afegir la informació 'name'
        foreach ($projectViewDataFields as $tableKey => $table) {
            if (!isset($table['config']) || !isset($table['config']['layout'])) {
                continue;
            }
            // Recorrem tots els layouts
            for ($i = 0; $i < count($table['config']['layout']); $i++) {

                // Recorrem totes les cel·les
                for ($j = 0; $j < count($table['config']['layout'][$i]['cells']); $j++)

                    // Si no s'ha assignat el name al layout es cerca el name al field
                    if (!isset($table['config']['layout'][$i]['cells'][$j]['name'])) {
                        $fieldName = $table['config']['layout'][$i]['cells'][$j]['field'];

                        // TODO[Xavi] Valorar si es preferible assignar el valor del field quan no existeixi 'name' al camp
                        $layoutName = $table['config']['fields'][$fieldName]['name'];
                        $projectViewDataFields[$tableKey]['config']['layout'][$i]['cells'][$j]['name'] = $layoutName;
                    }
                }
            }
    }

    protected function mergeFieldConfig($projectMetaData, &$projectViewDataFields) {
        foreach ($projectMetaData as $key=>$value) {
            if (!$value['keys']) {
                continue;
            }
            if (!isset($projectViewDataFields[$key]['config']) || !isset($projectViewDataFields[$key]['config']['fields'])) {
                $projectViewDataFields[$key]['config']['fields'] = [];
            }

            foreach ($value['keys'] as $field=>$fieldConfig) {
                // Si el camp no es troba al view, s'afegeix completament
                if (!isset($projectViewDataFields[$key]['config']['fields'][$field])) {
                    $projectViewDataFields[$key]['config']['fields'][$field] = $fieldConfig;
                } else {
                    // Si es troba al view, es comprova que el valor no estigui configurat, i en aquest cas s'afegeix la configuració del config
                    foreach ($fieldConfig as $fieldConfigKey=>$fieldConfigValue) {
                        if (!isset($projectViewDataFields[$key]['config']['fields'][$field][$fieldConfigKey])) {
                            $projectViewDataFields[$key]['config']['fields'][$field][$fieldConfigKey] = $fieldConfigValue;
                        } // si ja es troba establert a la view no fem res, perquè aquest te prioritat

                    }
                }
            }

        }

    }

    public function getProjectType() {
        return $this->projectType;
    }

    public function getViewConfigName() {
        return $this->viewConfigName;
    }

    public function setViewConfigName($viewConfigName) {
        $this->viewConfigName = $viewConfigName;
    }

    /**
     * Guarda los datos
     * @param array $toSet (s'ha generat a l'Action corresponent)
     */
    public function setData($toSet) {
        $toSet[ProjectKeys::KEY_METADATA_VALUE] = $this->processAutoFieldsOnSave($toSet[ProjectKeys::KEY_METADATA_VALUE]);
        $toSet[ProjectKeys::KEY_METADATA_VALUE] = $this->_updateCalculatedFieldsOnSave($toSet[ProjectKeys::KEY_METADATA_VALUE]);
        $this->metaDataService->setMeta($toSet);
    }

    /**
     * Guarda los datos del proyecto
     * @param JSON $dataProject Nou contingut de l'arxiu de dades del projecte
     */
    public function setDataProject($dataProject, $summary="") {
        $calculatedData = $this->processAutoFieldsOnSave($dataProject);
        $calculatedData = $this->_updateCalculatedFieldsOnSave($calculatedData);
        $this->projectMetaDataQuery->setMeta($calculatedData, $this->getMetaDataSubSet(), $summary);
    }

    private function processAutoFieldsOnSave($data) {
        $isArray = is_array($data);
        $values = $isArray?$data:json_decode($data, true);
        $configStructure = $this->getMetaDataDefKeys();
        foreach ($configStructure as $key => $def) {
            if(isset($def["calculateOnSave"])){
                $value = IocCommon::getCalculateFieldFromFunction($def["calculateOnSave"], $this->id, $values, $this->getPersistenceEngine());
                $values[$key]=$value;
            }elseif ($def["type"] == "boolean" || $def["type"] == "bool") {
                if(!isset($values[$key])
                        || $values[$key] === false
                        || $values[$key] === "false"){
                    $values[$key] = "false";
                }else{
                    $values[$key] = "true";
                }
            }
        }
        $data = $isArray?$values:json_encode($values);
        return $data;
    }

    private function processAutoFieldsOnRead($data, $configStructure=NULL) {
        $isArray = is_array($data);
        $values = $isArray ? $data : json_decode($data, true);
        if ($configStructure==NULL){
            $configStructure = $this->getMetaDataDefKeys();
        }
        foreach ($configStructure as $key => $def) {
            if (isset($def["calculateOnRead"])) {
                $value = IocCommon::getCalculateFieldFromFunction($def["calculateOnRead"], $this->id, $values, $this->getPersistenceEngine());
                $values[$key] = $value;
            }
        }
        $data = $isArray ? $values : json_encode($values);
        return $data;
    }

    private function processAutoFieldsAndUpdateCalculatedFieldsOnReadFromStructuredData($data){
        $dataKeyValue = array();
        foreach ($data as $item){
            $dataKeyValue[$item["id"]] = $item['value'];
        }
        $dataKeyValue = $this->processAutoFieldsOnRead($dataKeyValue, $data);
        $dataKeyValue = $this->_updateCalculatedFieldsOnRead($dataKeyValue);
        foreach ($data as $key => $item){
            $data[$key]['value'] = $dataKeyValue[$item["id"]];
        }
        return $data;
    }

    private function _updateCalculatedFieldsOnSave($data) {
        $isArray = is_array($data);
        $values = ($isArray) ? $data : json_decode($data, true);
        $values = $this->updateCalculatedFieldsOnSave($values);
        $data = ($isArray) ? $values : json_encode($values);
        return $data;
    }

    private function _updateCalculatedFieldsOnRead($data) {
        $isArray = is_array($data);
        $values = ($isArray) ? $data : json_decode($data, true);
        $values = $this->updateCalculatedFieldsOnRead($values);
        $data = ($isArray) ? $values : json_encode($values);
        return $data;
    }

    public function updateCalculatedFieldsOnSave($data) {
        // A implementar a les subclasses, per defecte no es fa res
        return $data;
    }

    public function updateCalculatedFieldsOnRead($data) {
        // A implementar a les subclasses, per defecte no es fa res
        return $data;
    }

    public function getDraft($peticio=NULL) {
        //un draft distinto por cada subset de un proyecto (mismo id para todo el proyecto)
        $draft = $this->draftDataQuery->getFull($this->id.$this->getMetaDataSubSet());
        if ($peticio)
            return $draft[$peticio]; // $peticio = 'content' | 'date'
        else
            return $draft;
    }

    public function getAllDrafts() {
        $drafts = [];
        if ($this->hasDraft()) {
            $drafts['project'] = $this->getDraft();
        }
        return $drafts;
    }

    private function hasDraft(){
        return $this->draftDataQuery->hasFull($this->id.$this->getMetaDataSubSet());
    }

    public function saveDraft($draft) {
        //un draft distinto para cada subset de un proyecto (mismo id para todo el proyecto)
        $this->draftDataQuery->saveProjectDraft($draft, $this->getMetaDataSubSet());
    }

    public function removeDraft() {
        $this->draftDataQuery->removeProjectDraft($this->id.$this->getMetaDataSubSet());
    }

    /**
     * Devuelve un array con la estructura definida en el archivo configMain.json
     */
    public function getMetaDataDefKeys() {
        //Cambiado por traspaso desde Dao a ProjectMetaDataQuery
//        $dao = $this->metaDataService->getMetaDataDaoConfig();
//        $struct = $dao->getMetaDataStructure($this->getProjectType(),
//                                             $this->getMetaDataSubSet(),
//                                             $this->persistenceEngine);
        $defKeys = $this->projectMetaDataQuery->getMetaDataDefKeys();
        return json_decode($defKeys, TRUE);
    }

    // Verifica que el $subSet estigui definit a l'arxiu de configuració (configMain.json)
    public function validaSubSet($subSet) {
        $subSetList = $this->projectMetaDataQuery->getListMetaDataSubSets();
        return in_array($subSet, $subSetList);
    }

    public function getPluginName(){
        $dir = $this->projectMetaDataQuery->getProjectTypeDir();
        $dirs  = explode("/", $dir);
        $ret = $dirs[count($dirs)-4];
        return  $ret;
    }

    //TODO PEL RAFA: AIXÒ HA DE PASSAR AL ProjectDataQuery
    //Obtiene un array [key, value] con los datos de una revisión específica del proyecto solicitado
    public function getDataRevisionProject($rev) {
        $file_revision = $this->projectMetaDataQuery->getFileName($this->id, [ProjectKeys::KEY_REV => $rev]);
        $subSet = $this->getMetaDataSubSet();
        $jrev = gzfile($file_revision);
        $todo = "";
        foreach ($jrev as $part)
            $todo .= $part;
        $a = json_decode($todo, TRUE);
        return $a[$subSet];
    }

    //TODO PEL RAFA: AIXÒ HA DE PASSAR AL ProjectDataQuery
    //Obtiene la fecha de una revisión específica del proyecto solicitado
    public function getDateRevisionProject($rev) {
        $file_revision = $this->projectMetaDataQuery->getFileName($this->id, [ProjectKeys::KEY_REV => $rev]);
        $date = @filemtime($file_revision);
        return $date;
    }

    /**
     * Indica si el proyecto ya existe
     * @return boolean
     */
    public function existProject() {
        return $this->projectMetaDataQuery->existProject();
    }

    /**
     * Indica si el proyecto ya ha sido generado
     * @return boolean
     */
    public function isProjectGenerated() {
        return $this->projectMetaDataQuery->isProjectGenerated();
    }

    public function getProjectSubSetAttr($att) {
        return $this->projectMetaDataQuery->getProjectSystemSubSetAttr($att);
    }

    public function setProjectSubSetAttr($att, $value) {
        return $this->projectMetaDataQuery->setProjectSystemSubSetAttr($att, $value);
    }

    public abstract function generateProject();

    //Del fichero _wikiIocSystem_.mdpr, del proyecto en curso, el elemento subSet solicitado
    public function getSystemData($subSet=FALSE) {
        return $this->projectMetaDataQuery->getSystemData($subSet);
    }

    public function setSystemData($data, $subSet=FALSE) {
        $this->projectMetaDataQuery->setSystemData($data, $subSet);
    }

    //Del fichero _wikiIocSystem_.mdpr del proyecto en curso, obtiene un atributo del subSet solicitado
    public function getProjectSystemSubSetAttr($attr, $subSet=NULL) {
        return $this->projectMetaDataQuery->getProjectSystemSubSetAttr($attr, $subSet);
    }

    public function setProjectSystemSubSetAttr($attr, $value, $subSet=NULL) {
        return $this->projectMetaDataQuery->setProjectSystemSubSetAttr($attr, $value, $subSet);
    }

    /*
     * Del archivo configMain.json, obtiene el atributo solicitado de la clave principal solicidada
     */
    public function getMetaDataAnyAttr($attr=NULL, $configMainKey=NULL) {
        return $this->projectMetaDataQuery->getMetaDataAnyAttr($attr, $configMainKey);
    }


    /**
     * @param integer $num Número de revisiones solicitadas El valor 0 significa obtener todas las revisiones
     * @return array  Contiene $num elementos de la lista de revisiones del fichero de proyecto obtenidas del log .changes
     */
    public function getProjectRevisionList($num=0) {
        $revs = $this->projectMetaDataQuery->getProjectRevisionList($num);
        if ($revs) {
            $amount = WikiGlobalConfig::getConf('revision-lines-per-page', 'wikiiocmodel');
            if (($revs["totalamount"] = count($revs)) > $amount) {
                $revs['show_more_button'] = true;
                $revs["maxamount"]=$amount;
            }else{
                $revs["maxamount"]=$revs["totalamount"];
            }
            $r = $this->getActualRevision();
            $this->setActualRevision(TRUE);
            $revs['current'] = @filemtime($this->projectMetaDataQuery->getFileName($this->id));
            $this->setActualRevision($r);
            $revs['docId'] = $this->id;
            $revs['position'] = -1;
            $revs['amount'] = $amount;
        }
        return $revs;
    }

    public function getLastModFileDate() {
        return $this->projectMetaDataQuery->getLastModFileDate();
    }

    public function getProjectTypeConfigFile() {
        return $this->projectMetaDataQuery->getListMetaDataComponentTypes(ProjectKeys::KEY_METADATA_PROJECT_CONFIG,
                                                                          ProjectKeys::KEY_MD_PROJECTTYPECONFIGFILE);
    }

    public function getMetaDataComponent($projectType, $type){
        //$dao = $this->metaDataService->getMetaDataDaoConfig(); Anulado por TRASPASO a projectMetaDataQuery
        $set = $this->projectMetaDataQuery->getMetaDataComponentTypes($this->getMetaDataSubSet(), $projectType);
        if ($set) {
            $subset = $set[$type];
            $ret = is_array($subset) ? "array" : $subset;
        }
        return $ret;
    }

    public function preUpgradeProject($subSet) {
        if(class_exists("systemUpgrader")){
            $ret = systemUpgrader::preUpgrade($this, $subSet);
        }else{
            $ret = true;
        }
        return $ret;
    }

    public function createTemplateDocument($data=NULL){
        //NO HI HA TEMPLATES A CREAR
    }

    /**
     * Retorna el nom e la plantilla corresponent al document.
     *
     * @param array|string $responseData ruta de la plantilla, nom de la plantilla o objecte de configuració
     * @return string nom de la plantilla
     */
    public function getTemplateContentDocumentId($responseData){

        // Pot tractar-se del nom de la plantilla o una ruta, extraiem el nom i el retornem
        if (is_string($responseData)) {
            $plantilla = $responseData;

        } else {
            $plantilla = $responseData["plantilla"];

            if ($plantilla === NULL) {
                $plantilla = $responseData['projectMetaData']["plantilla"]['value'];
            }
        }

        $lastPos = strrpos($plantilla, ':');

        if ($lastPos) {
            $plantilla = substr($plantilla, $lastPos+1);
        }

        return $plantilla;

    }

    public function getTemplatePath($templateName, $version = null){
        $path = $this->getProjectMetaDataQuery()->getProjectTypeDir()."metadata/plantilles/" . $templateName . ".txt";

        if ($version) {
            $path .= "." . $version;
        }

        return $path;
    }

    /**
     * Obtiene la lista de ficheros de la clave metaDataFtpSender del configMain.json
     * @return array con los nombres de los ficheros
     */
    public function getMetaDataFtpSenderFiles() {
        return $this->_constructArrayFileNames($this->id, $this->getMetaDataFtpSender("files"));
    }

    /**
     * Construye la lista de ficheros a partir del array recibido
     * @return array con los nombres de los ficheros
     */
    private function _constructArrayFileNames($name, $metaDataFtpSender=NULL) {
        if ($metaDataFtpSender) {
            $ret = array();
            $output_filename = str_replace(":", "_", $name);
            foreach ($metaDataFtpSender as $value) {
                $suff = (empty($value['suffix'])) ? "" : "_{$value['suffix']}";
                $ret[] = "${output_filename}${suff}.{$value['type']}";
            }
        }
        return $ret;
    }

    /**
     * Obtiene datos de la clave metaDataFtpSender del configMain.json
     * @return (si $key==NULL) array asociativo sobre ficheros [name [local, type, action]]
     * @return (si tiene $key) array
     */
    public function getMetaDataFtpSender($key=NULL, $metaDataSubset=FALSE) {
        return $this->getProjectMetaDataQuery()->getMetaDataFtpSender($key, $metaDataSubset);
    }

    /**
     * Obtiene la lista de ficheros, y sus propiedades, (del configMain.json) que hay que enviar por FTP
     * @return array
     */
    public function filesToExportList() {
        $ret = array();
        $connData = $this->getFtpConfigData();
        $metadata = $this->getMetaDataFtpSender();
        if (!empty($metadata["files"])) {
            foreach ($metadata["files"] as $n => $objFile) {
                $suff = (empty($objFile['suffix'])) ? "" : "_{$objFile['suffix']}";
                $path = ($objFile['local']==='mediadir') ? WikiGlobalConfig::getConf('mediadir')."/".str_replace(':', '/', $this->id)."/" : $objFile['local'];
                if (($dir = @opendir($path))) {
                    while ($file = readdir($dir)) {
                        if (!is_dir("$path/$file") && preg_match("/.+${suff}\.{$objFile['type']}$/", $file) ) {
                            $ret[$n]['file'] = $file;
                            $ret[$n]['local'] = $path;
                            $ret[$n]['action'] = $objFile['action'];
                            $rBase = (empty($objFile['remoteBase'])) ? (empty($metadata['remoteBase'])) ? $connData["remoteBase"] : $metadata['remoteBase'] : $objFile['remoteBase'];
                            $rDir  = (empty($objFile['remoteDir'])) ? (empty($metadata['remoteDir'])) ? $connData["remoteDir"] : $metadata['remoteDir'] : $objFile['remoteDir'];
                            $ret[$n]['remoteBase'] = $rBase;
                            $ret[$n]['remoteDir'] = $rDir;
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Averigua si hay fichero para enviar por FTP
     * @return boolean
     */
    public function haveFilesToExportList() {
        $ret = $this->filesToExportList();
        return (!empty($ret));
    }

    public function getFtpConfigData($ftpId=FALSE){
        if (!$ftpId){
            $ftpId = $this->getMetaDataFtpSender(ProjectKeys::KEY_FTPID);
        }
        $pluguin = $this->getPluginName();
        $ftpConfigs =  WikiGlobalConfig::getConf(ProjectKeys::KEY_FTP_CONFIG, $pluguin);
        if(!isset($ftpConfigs["default"]) && !isset($ftpConfigs[$ftpId]) ){
            throw new Exception("Cal configurar les dades del servidor FTP");
        }
        $connectionData = !isset($ftpConfigs["default"]) ? [] : $ftpConfigs['default'];
        if (isset($ftpConfigs[$ftpId])){
            $connectionData = array_merge($connectionData, $ftpConfigs[$ftpId]);
        }
        return $connectionData;
    }

    /**
     * Guarda, en el fitxer _wikiIocSystem_.mdpr (chivato), la data del fitxer 'HTML export' que s'ha enviat a FTP
     * (només s'utilitza el primer fitxer de la llista)
     */
    public function set_ftpsend_metadata() {
        $mdFtpSender = $this->getMetaDataFtpSender();
        $fileNames = $this->_constructArrayFileNames($this->id, $mdFtpSender["files"]);

        $file = WikiGlobalConfig::getConf('mediadir')."/". preg_replace('/:/', '/', $this->id)."/".$fileNames[0];
        $this->projectMetaDataQuery->setProjectSystemStateAttr("ftpsend_timestamp", filemtime($file));
    }

    /**
     * Comprova si els fitxers 'HTML export' han estat enviats a FTP
     * (només s'utilitza el primer fitxer de la llista)
     * @return string HTML per a les metadades
     */
    public function get_ftpsend_metadata() {
        $connData = $this->getFtpConfigData();
        $mdFtpSender = $this->getMetaDataFtpSender();
        $fileNames = $this->_constructArrayFileNames($this->id, $mdFtpSender['files']);

        $file = WikiGlobalConfig::getConf('mediadir').'/'. preg_replace('/:/', '/', $this->id) . '/' . $fileNames[0];
        $class = "mf_zip";
        $html = '';
        $savedtime = $this->projectMetaDataQuery->getProjectSystemStateAttr("ftpsend_timestamp");

        $fileexists = @file_exists($file);
        if ($fileexists) $filetime = filemtime($file);

        if ($fileexists && $savedtime === $filetime) {
            foreach ($mdFtpSender['files'] as $objFile) {
                $index = (empty($objFile['remoteIndex'])) ? $mdFtpSender['remoteIndex'] : $objFile['remoteIndex'];
                if (empty($index)) {
                    $outfile = str_replace(":", "_", $this->id);
                    $suff = (empty($objFile['suffix'])) ? "" : "_{$objFile['suffix']}";
                    $index = "${outfile}${suff}.{$objFile['type']}";
                }
                $rDir  = (empty($objFile['remoteDir'])) ? (empty($mdFtpSender['remoteDir'])) ? $connData["remoteDir"] : $mdFtpSender['remoteDir'] : $objFile['remoteDir'];
                if (in_array(1, $objFile['action'])) {
                    $rDir .= pathinfo($file, PATHINFO_FILENAME)."/";  //es una action del tipo unzip
                }
                $url = "{$connData['remoteUrl']}${rDir}${index}";
                $data = date("d/m/Y H:i:s", $filetime);
                $class = "mf_{$objFile['type']}";

                $linkRef = empty($objFile['linkName'])?$index:$objFile['linkName'];
                $html.= '<p><span id="ftpsend" style="word-wrap: break-word;">';
                $html.= '<a class="media mediafile '.$class.'" href="'.$url.'" target="_blank">'.$linkRef.'</a> ';
                $html.= '<span style="white-space: nowrap;">'.$data.'</span>';
                $html.= '</span></p>';
            }
        }else{
            $html.= '<span id="ftpsend">';
            $html.= '<p class="media mediafile '.$class.'">No hi ha cap fitxer pujat al FTP</p>';
            $html.= '</span>';
        }

        return $html;
    }

    public function getRoleData(){
        $ret = array();
        $data = $this->getCurrentDataProject();
        if ($data) { //En la creación de proyecto $data es NULL
            $struct = $this->getMetaDataDefKeys();
            foreach ($struct as $field => $cfgField) {
                if (isset($cfgField["isRole"]) && $cfgField["isRole"]){
                    $ret[$field] = $data[$field];
                }
            }
        }
        return $ret;
    }
}
