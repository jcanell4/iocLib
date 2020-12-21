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
    public function act_draftdel(){
        global $INFO;
        @unlink($INFO['draft']);
        $INFO['draft'] = null;
        return 'show';
    }


    public static function getCalculateFieldFromFunction($calcDefProp, $projectId, $values, $persistence=NULL) {
        if (isset($calcDefProp)) {
            $className = $calcDefProp['class'];
            $calculator = new $className;
            if ($calculator) {
                //init
                if($calculator->isCalculatorOfTypeData(ICalculateWithProjectId::WITH_PROJECT_ID_TYPE)){
                    $calculator->init($projectId, ICalculateWithProjectId::WITH_PROJECT_ID_TYPE);
                }
                if($calculator->isCalculatorOfTypeData(ICalculateFromValues::FROM_VALUES_TYPE)){
                    $calculator->init($values, ICalculateFromValues::FROM_VALUES_TYPE);
                }
                if($calculator->isCalculatorOfTypeData(ICalculateWithPersistence::WITH_PERSISTENCE_TYPE)){
                    if($persistence==NULL){
                        $persistence = $this->getPersistenceEngineFromPlugincontroller();
                    }
                    $calculator->init($persistence, ICalculateWithPersistence::WITH_PERSISTENCE_TYPE);
                }
                $value = $calculator->calculate($calcDefProp['data']);
            }
        }
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
        $info ['timestamp'] = $infoA['timestamp'];
        $info ['id']        = $infoA['id'];

        $messageStack = [ ];

        if ( is_string( $infoA ['message'] ) ) {
            $messageStack[] = $infoA['message'];
        } else if ( is_array( $infoA['message'] ) ) {
            $messageStack = $infoA['message'];
        }

        if ( is_string( $infoB ['message'] ) ) {
            $messageStack[] = $infoB['message'];
        } else if ( is_array( $infoB['message'] ) ) {
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
                                      'pf' => array (
                                                'urlBase' => "lib/exe/ioc_ajax.php?call=page",
                                                'params' => [ResponseHandlerKeys::PROJECT_OWNER,
                                                             ResponseHandlerKeys::PROJECT_SOURCE_TYPE]
                                              ),
                                      's' => array (
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
}
