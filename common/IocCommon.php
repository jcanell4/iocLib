<?php
/**
 * Class ioc_common: Contiene funciones comunes
 * @culpable Rafael
 */
if (!defined('DOKU_INC')) die();
require_once (DOKU_INC . 'inc/pageutils.php');
require_once(DOKU_TPL_INCDIR.'conf/cfgIdConstants.php');

class IocCommon {
    /*
     * Run permissionchecks
     */
    public function act_permcheck($act){
        global $INFO;

        if (in_array($act, array('save','preview','edit','recover'))) {
            if ($INFO['exists']){
                $permneed = ($act == 'edit') ? AUTH_READ : AUTH_EDIT;
            }else{
                $permneed = AUTH_CREATE;
            }
        }elseif(in_array($act, array('login','search','recent','profile','profile_delete','index', 'sitemap'))){
            $permneed = AUTH_NONE;
        }elseif($act == 'revert'){
            $permneed = ($INFO['ismanager']) ? AUTH_EDIT : AUTH_ADMIN;
        }elseif($act == 'register'){
            $permneed = AUTH_NONE;
        }elseif($act == 'resendpwd'){
            $permneed = AUTH_NONE;
        }elseif($act == 'admin'){
            $permneed = ($INFO['ismanager']) ? AUTH_READ : AUTH_ADMIN;
        }else{
            $permneed = AUTH_READ;
        }

        if ($INFO['perm'] >= $permneed) {
            return $act;
        }else {
            return 'denied';
        }
    }

    /*
     * Deletes the draft for the current page and user
     */
    public function act_draftdel($act="show"){
        global $INFO;
        @unlink($INFO['draft']);
        $INFO['draft'] = null;
        return 'show';
    }


    public static function getCalculateFieldFromFunction($calcDefProp, $projectId, $values, $persistence=NULL, $defaultValue=NULL) {
        if (isset($calcDefProp)) {
            $className = $calcDefProp['class'];
            $calculator = new $className;
            if ($calculator) {

                //init
                if($calculator->isCalculatorOfTypeData(ICalculateWithProjectId::WITH_PROJECT_ID_TYPE)){
                    $calculator->init($projectId, ICalculateWithProjectId::WITH_PROJECT_ID_TYPE, $defaultValue);
                }

                if($calculator->isCalculatorOfTypeData(ICalculateWithConfigMain::WITH_CONFIG_MAIN_TYPE)){
                    $configMain = static::getConfigMainFromPlugincontroller();
                    $calculator->init($configMain, ICalculateWithConfigMain::WITH_CONFIG_MAIN_TYPE);
                }

                if($calculator->isCalculatorOfTypeData(ICalculateFromValues::FROM_VALUES_TYPE)){
                    $calculator->init($values, ICalculateFromValues::FROM_VALUES_TYPE, $defaultValue);
                }

                if($calculator->isCalculatorOfTypeData(ICalculateWithPersistence::WITH_PERSISTENCE_TYPE)){
                    if($persistence==NULL){
                        $persistence = static::getPersistenceEngineFromPlugincontroller();
                    }
                    $calculator->init($persistence, ICalculateWithPersistence::WITH_PERSISTENCE_TYPE, $defaultValue);
                }


                $value = $calculator->calculate($calcDefProp['data']);
            }
        }
        return $value;
    }
    
    public static function getValidatorValueFromExpression($expression, $permission, $responseData){
        $funcREadOnly = $expression;
        if(isset($funcREadOnly["or"])){
            $value=FALSE;
            foreach ($funcREadOnly["or"] as $readOnlyValidator){
                $value = $value || self::getValidatorValueFromExpression($readOnlyValidator, $permission, $responseData);
            }
        }else if(isset($funcREadOnly["and"])){
            $value=TRUE;
            foreach ($funcREadOnly["and"] as $readOnlyValidator){
                $value = $value && self::getValidatorValueFromExpression($readOnlyValidator, $permission, $responseData);
            }
        }else{
            $value = self::getValidatorValue($funcREadOnly, $permission, $responseData);
        }
        return $value;
    }

    
    public static function getValidatorValue($outArrValues, $permission, $responseData){
        $className = $outArrValues['class'];
        $validator = new $className;

        if (!$validator) {
            // TODO: la classe no existeix, llençar execepció
            return;
        }
        $validatorTypeData = $validator->getValidatorTypeData();
        switch ($validatorTypeData){
            case "permission":
                $validator->init($permission);
                break;
            case "response":
                $validator->init($responseData);
                break;
        }
        $value = $validator->validate($outArrValues['data']);
        return $value;
    }


    private static function getPersistenceEngineFromPlugincontroller(){
        global $plugin_controller;
        if(is_callable([$plugin_controller, "getPersistenceEngine"])){
            return $plugin_controller->getPersistenceEngine();
        }else{
            throw new Exception("Es necessita la persistència per poder continuar");
        }
    }

    /**
     * Genera un element amb la informació correctament formatada i afegeix el timestamp.
     * Per generar un info associat al esdeveniment global s'ha de passar el id com a buit
     *
     * @param string          $type     - tipus de missatge
     * @param string|string[] $message  - Missatge o missatges associats amb aquesta informació
     * @param string          $id       - id del document al que pertany el missatge
     * @param int             $duration - Si existeix indica la quantitat de segons que es mostrarà el missatge
     *
     * @return array - array amb la configuració de l'item de informació
     */
    public function generateInfo($type, $message, $id='', $duration=-1, $subSet=NULL) {
        if ($id !== '' && $subSet && $subSet !== ProjectKeys::VAL_DEFAULTSUBSET) {
            $id .= "-$subSet";
        }
        return [
            'id'        => str_replace(':', '_', $id),
            'type'      => $type,
            'message'   => $message,
            'duration'  => $duration,
            'timestamp' => date("d-m-Y H:i:s")
        ];
    }

    /**
     * Aquesta funció reb 2 estructures tipus missatge i les mescla en una única estructura que conté els 2 missatges
     *
     * En els casos en que hi hagi discrepancies i no hi hagi cap preferencia es fa servir el valor de A
     * Els tipus global de la info serà el de major gravetat: "debug" > "error" > "warning" > "info"
     *
     * @param {array} $infoA, $infoB : Estructures tipus missatge pel generador de respostes
     * @return {array} Composició dels missatges pel generador de respostes
     */
    public function addInfoToInfo( $infoA, $infoB ) {
        $info = [];
        if (!$infoA && !$infoB)
            return NULL;
        elseif (!$infoA)
            return $infoB;
        elseif (!$infoB)
            return $infoA;
        
        if (!is_array($infoA)) $infoA = [];
        if (!is_array($infoB)) $infoB = [];

        if ( $infoA['type'] == 'debug' || $infoB['type'] == 'debug' ) {
            $info['type'] = 'debug';
        } else if ( $infoA['type'] == 'error' || $infoB['type'] == 'error' ) {
            $info['type'] = 'error';
        } else if ( $infoA['type'] == 'warning' || $infoB['type'] == 'warning' ) {
            $info['type'] = 'warning';
        } else {
            $info['type'] = $infoA['type'];
        }

        // Si algun dels dos te duració ilimitada, aquesta perdura
        if ( $infoA['duration'] == - 1 || $infoB['duration'] == - 1 ) {
            $info['duration'] = -1;
        } else {
            $info['duration'] = $infoA['duration'];
        }

        // El $id i el timestamp ha de ser el mateix per a tots dos
        $info['timestamp'] = $infoA['timestamp'];
        $info['id']        = $infoA['id'];

        $messageStack = [ ];

        if ( is_string($infoA['message']) ) {
            $messageStack[] = $infoA['message'];
        } else if ( is_array($infoA['message']) ) {
            $messageStack = $infoA['message'];
        }

        if ( is_string($infoB['message']) ) {
            $messageStack[] = $infoB['message'];
        } else if (is_array($infoB['message']) ) {
            $messageStack = array_merge($messageStack, $infoB['message']);
        }

        $info['message'] = $messageStack;

        return $info;
    }

    public function addResponseTab($dades, &$ajaxCmdResponseGenerator) {
        $containerClass = "ioc/gui/ContentTabNsTreeListFromPage";
        $urlBase = "lib/exe/ioc_ajax.php?call=page";
        $urlTree = "lib/exe/ioc_ajaxrest.php/ns_tree_rest/";

        $contentParams = array(
            "id" => cfgIdConstants::TB_SHORTCUTS,
            "title" =>  $dades['title'],
            "standbyId" => cfgIdConstants::BODY_CONTENT,
            "urlBase" => $urlBase,
            "data" => $dades["content"],
            "treeDataSource" => $urlTree,
            'typeDictionary' => array('p' => array (
                                                'urlBase' => "lib/exe/ioc_ajax.php?call=project",
                                                'params' => [ResponseHandlerKeys::PROJECT_TYPE]
                                             ),
                                      'p#w' => array (
                                                'urlBase' => "lib/exe/ioc_ajax.php?call=project&do=workflow&action=view",
                                                'params' => [ProjectKeys::PROJECT_TYPE]
                                              ),
                                      'pf' => array (
                                                'urlBase' => "lib/exe/ioc_ajax.php?call=page",
                                                'params' => [ResponseHandlerKeys::PROJECT_OWNER,
                                                             ResponseHandlerKeys::PROJECT_SOURCE_TYPE]
                                              ),
                                      's' => array (
                                                'urlBase' => "lib/exe/ioc_ajax.php?call=project",
                                                'params' => [ProjectKeys::PROJECT_TYPE,
                                                             ProjectKeys::KEY_METADATA_SUBSET]
                                              ),
                                      'sh' => array (
                                                'urlBase' => "lib/exe/ioc_ajax.php?call=project",
                                                'params' => [ProjectKeys::PROJECT_TYPE,
                                                             ProjectKeys::KEY_METADATA_SUBSET]
                                              )
                                     )
        );
        $ajaxCmdResponseGenerator->addAddTab(cfgIdConstants::ZONA_NAVEGACIO,
                                             $contentParams,
                                             ResponseHandlerKeys::FIRST_POSITION,
                                             $dades['selected'],
                                             $containerClass
                                            );

    }

    public static function getFormat($id="", $def="undefined"){
        if (preg_match('/.*-(.*)$/', $id, $matches)) {
            return $matches[1];
        } else {
            return $def;
        }
    }

    /**
     * Extreu la/les propietat/s indicadas a $fields d'una construcció del tipus 
     * link o media de la wiki. que segueixi alguna de les següents sintaxis:
     * 1. [[url/link|text]] on text és el text visible per vincular la url amb 
     *    independència de quin renderer es faci servir. Sempre es retorna text
     * 2. [[url/link|texthtml#textpdf]] on texthtml és un text per vincular l'enllaç 
     *    en la renderització html i textpdf es el text destinat a vinculat la url
     *    en la versió pdf.
     * 3. [[url/link|{“html”:”text per html”,”pdf”:”text per pdf”}]]. on el títol 
     *    presenta un format JSON amb dos camps html i pdf.
     * 4. {{ns:imatgeInterna|text/offset?}} destinat a afegir imatges de tipus B en les 
     *    que el text associat a la imatge, a l'atribut title i l'atribut alt, 
     *    prenguin el valor text. El valor offset és opcional i pot contenir 
     *    un valor numèric per determminar l'offset aplicat a la renderització PDF
     * 5. {{ns:imatgeInterna|text associat a la imatge B i a l'atrubut title#text per a cecs/offset?}} 
     *    i en el que el text es troba dividit en dos fragments separats per #. El
     *    primer fragment està destinat a afegir a les imatges de tipus B el text 
     *    associat a la imatge i el títol. El segon fragment defineix lel valor de 
     *    l'atribut alt. El valor offset és opcional i pot contenir un valor 
     *    numèric per determminar l'offset aplicat a la renderització PDF
     * 6. {{ns:imatgeInterna|["title":"text associat a la imatge B i a l'atribut title", "alt":"text per a cecs", "offset":99]/offset?}}.
     *    Aquí, els textos associats a la imatge B es defineixen per un objecte 
     *    amb sintaxi JSON però delimitat per [] en comptes de {}. El camp title 
     *    s'associarà a l'atribut title de l'etiqieta img i al text associat a 
     *    la imatge B, el camp alt s'assignarà al l'atribut alt de l'etiqueta img. 
     *    El valor offset usat en la renderització pdf és opcional i es pot definir
     *    de dues maneres: fent servir un camp anomenat offset de l'objecte JSON o
     *    bé fent servir la notació classica on l'ofsset es troba al final del 
     *    text i es separa amb el caràcter /.
     * El valor $type indica si la sintaxi esperada és de tipus link [[...]] o de 
     * tipus imatge B {{...}}. El valor de $fields indica quin o quins camps es volen 
     * retornar. Admet una cadena amb el nom d'un dels camps o un array amb el
     * camp o camps que la funció ha d’extreure de $comment i retornar-ho. 
     * Si $fields és una cadena es retornarà un string amb el valor del camp si 
     * existeix o una cadena buida en cas contrari. Si $fields és un array es 
     * retornarà un array associatiu amb el valor de cada camp (cadena buida si 
     * no existeix). El nom del camp farà de clau en l’array associatiu retornat. 
     * Per tipus ‘link’ s’admeten els valors de camps: ‘html’ i ‘pdf’. Per tipus 
     * ‘media’ s’admeten els camps: ‘html’, ‘pdf’, ‘title', ‘alt’ i 'offset’. En el 
     * tipus ‘media’, el camp ‘html’ és equivalent a passar un array amb el  valor 
     * array(’title', ‘alt’). El camp ‘pdf’ és equivalent a passar el camp ‘title’. 
     * Aquest canvis s’han fet per compatibilitzar amb la versió original en la que 
     * només es feia servir html o pdf. D’aquesta manera evitem canvis a altres parts 
     * del codi, sense necessitat.
     * @param string $type - Objecte sol·licitant: 'link', 'media'
     * @param mixed $fields paràmetre de tipus mixed que pot ser un cadena o un array de 
     * cadevnes amb algun dels valors següents: 'pdf', 'html', 'name', 'short', 
     * 'title', 'alt', 'offset'
     * @param string $comment - Cadena en format: JSON, 'JSON delimitat per []', 
     * 'amb #' o 'string', que conté les diverses posibilitats de títol.
     * @return string o array
     */
    public static function formatTitleExternalLink($type, $fields, $comment="") {
        $inputTypesFromRender = array(
                                "link" => array(
                                    "html" => "html", 
                                    "pdf" => "pdf"), 
                                "media" => array(
                                    "html" => array("title", "alt"),
                                    "pdf" => "title",
                                    "title" => "title", 
                                    "alt" => "alt",  
                                    "offset" => "offset"),
                                "file" => array(
                                    "html" => array("title", "nobreak"),
                                    "pdf" => "title"));   
        $ret = $comment;
        if (!empty($comment)) {
            if(is_string($fields) && isset($inputTypesFromRender[$type][$fields])){
                $field_or_fields = $inputTypesFromRender[$type][$fields] ;
            }else{
                $field_or_fields = $fields;
            }            
            if ($comment[0] === "[" || $comment[0] === "{" ) {
                $comment = preg_replace('/\/[+-]?\d+$/', '', $comment); //elimina el 'offset'
                $comment = str_replace(['[',']'], ['{','}'], $comment);  //format JSON
                $comment = preg_replace(['/\{ *\&quot\;/','/\&quot\;\} */','/\&quot\; *: *\&quot\;/','/\&quot\; *, *\&quot\;/'], ['{"','"}','":"','","'], $comment);  //format JSON
                $arr_titol = json_decode($comment, true);
            }elseif (strpos($comment, "#") !== FALSE) {
                $t1 = $comment;
                $t2 = "";
                if (preg_match("/(?<!&amp;)#|\#(?!\d+;)/", $comment) || preg_match("/(?<!&)#|\#(?!\d+;)/", $comment)) {
                    // busca &#39; o &amp;#39; (comilla simple o torturada per htmlentities())
                    if (preg_match("/&amp;#\d+;/", $comment)) {
                        $s = preg_split("/(?<!&amp;)#|\#(?!\d+;)/", $comment);
//                    }elseif (preg_match("/&#\d+;/", $comment)) {
//                        $s = preg_split("/(?<!&)#|\#(?!\d+;)/", $comment);
                    }else{
                        $s = preg_split("/(?<!&)#|\#(?!\d+;)/", $comment);
                    }
                    $t1 = $s[0];
                    $t2 = preg_replace('/\/[+-]?\d+$/', '', $s[1]); //elimina el 'offset'
                }
                if(!empty($t2)){
                    if($type == "link"){
                        $arr_titol = array("html" => $t1, "pdf" => $t2);
                    }else if($type == "media"){
                        $arr_titol = array("title" => $t1, "alt" => $t2);
                    }else{
                        $nbreak = str_ends_with($t2, "/++");
                        $arr_titol = array("title" => $t1, "nobreak" => $nbreak);
                    }
                }
            }else if($type == "file"){
                $comment = preg_replace('/\/[+-]?\d+$/', '', $comment); //elimina el 'offset'
                $nbreak = preg_match("/\/\+\+$/",  $comment);
                if($nbreak){
                    $comment = preg_replace('/\/\+\+$/', '', $comment); //elimina el break'
                }
                $arr_titol = array("title" => $comment, "nobreak" => $nbreak);
            }
            if(isset($arr_titol)) {                    
                if(is_array($field_or_fields)){
                    $ret = array();
                    foreach ($field_or_fields as $field) {
                        $ret[$field] = self::__getCommentFieldValues($arr_titol, $field);
                    }
                }else{
                    $ret = self::__getCommentFieldValues($arr_titol, $field_or_fields);
                }
            }
        }
        return $ret;
    }
    
    /**
     * Aques mètode obté el valor del camp ($field) passat per paràmetre i contingut 
     * com a clau a l'array associatiu $aComment o en el seu defecte algun dels 
     * seus alies acceotats a les sintaxis WIKI analitzades.
     * @param Array $aComment. És una array associatiu amb els valor dels camps a 
     * extreure.
     * @param String $field. Ës el nom del camp a extreure des de $aComment. Aquest 
     * nom o algun dels seus àlies ha d'existir dins l'array associatiu $aComment. 
     * En cas contrari es retornarà una cadena buida
     * @return String
     */
    private static function __getCommentFieldValues($aComment, $field){
        $commentFields = array(
                            "text" => array("text", "default"), 
                            "short" => array("short", "default"), 
                            "title" => array("title"),
                            "alt" => array("alt", "description"),
                            "offset" => array("offset"));
        if(isset($commentFields[$field])){
            $fieldCandidates = $commentFields[$field];
        }else{
            $fieldCandidates = array($field);
        }
        $found = false;
        foreach ($fieldCandidates as $candidate) {
            if(!$found && isset($aComment[$candidate])){
                $found = $aComment[$candidate];
            }
        }
        return $found?$found:"";
    }

    public static function removeDir($directory) {
        if (!file_exists($directory) || !is_dir($directory)) {
            $ret = FALSE;
        }elseif(!is_readable($directory)) {
            $ret = FALSE;
        }else {
            $dh = opendir($directory);

            while ($contents = readdir($dh)) {
                if ($contents != '.' && $contents != '..') {
                    $path = "$directory/$contents";
                    if (is_dir($path)) {
                        self::removeDir($path);
                    }else {
                        unlink($path);
                    }
                }
            }
            closedir($dh);

            $ret = TRUE;
            if (file_exists($directory)) {
                $ret = rmdir($directory);
            }
        }
        return $ret;
    }

    public static function countRevisions($id, $media=false){
        $ret = 0;
        if ($media) {
            $fileName = mediaMetaFN($id, '.changes');
        } else {
            $fileName = metaFN($id, '.changes');
        }
        if (@file_exists($fileName)){
            $file = new \SplFileObject($fileName, 'r');
            $file->seek(PHP_INT_MAX);
            $ret = $file->key() - 1;
        }
        return $ret;
    }

    // Busca si algún elemento de $array1 está incluido en $array2
    public static function array_in_array($array1, $array2) {
        $has = FALSE;
        if (!is_array($array1)) $array1 = array($array1);
        if (!is_array($array2)) $array2 = array($array2);
        foreach ($array1 as $elem) {
            if (in_array($elem, $array2)) {
                $has = TRUE;
                break;
            }
        }
        return $has;
    }

    /**
     * Devuelve un valor no nulo.
     * @param mixed $param : valor d'entrada a evaluar
     * @param mixed $default : valor per defecte a assignar si el valor d'entrada és null
     * @return mixed : valor no nul
     */
    public static function nz($param=NULL, $default="") {
        return ($param==NULL || empty($param) || !isset($param)) ? $default : $param;
    }

    /**
     * Evalua el valor d'entrada per verificar si es tracta d'un array o un json convertible en array i intenta retornar un array
     * @param mixed $value : valor d'entrada a examinar
     * @param string $returnType : tipus de dada retornada després de l'avaluació: string, array, null
     * @param string $returnValue : valor de retorn per defecte en al cas que el valor d'entrada es transformi a null
     */
    public static function toArrayThroughArrayOrJson($value, $returnType="array", $returnValue=0) {
        if (is_array($value)) {
            $ret = $value;
        }else {
            $ret = json_decode($value, true);
            if ($ret == NULL) {
                switch ($returnType) {
                    case "array": $ret = []; break;
                    case "string": $ret = ""; break;
                    case "boolean":
                    case "number": $ret = $returnValue; break;
                }
            }
        }
        return $ret;
    }

    private static function getConfigMainFromPlugincontroller(){
        global $plugin_controller;



//        if(is_callable([$plugin_controller, "getProjectFile"])){
//            return $plugin_controller->getProjectFile();
//        }else{
//            throw new Exception("Es necessita la persistència per poder continuar");
//        }

        if(is_callable([$plugin_controller, "getProjectFile"])){
            $model = $plugin_controller->getCurrentProjectModel();
            $data = $model->getMetaDataJsonFile();
        }else{
            throw new Exception("Es necessita la persistència per poder continuar");
        }

        return $data;
    }
}
