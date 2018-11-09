<?php
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_COMMAND')) define('DOKU_COMMAND', DOKU_INC."lib/plugins/ajaxcommand/");
require_once(DOKU_COMMAND.'defkeys/ResponseHandlerKeys.php');
require_once(DOKU_COMMAND.'defkeys/ProjectKeys.php');
require_once(DOKU_TPL_INCDIR.'conf/cfgIdConstants.php');
/**
 * Class ioc_common: Contiene funciones comunes
 * @culpable Rafael
 */
class IocCommon {

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
    public function generateInfo($type, $message, $id='', $duration=-1) {
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
        $containerClass = "ioc/gui/ContentTabNsTreeListFromPage";;
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
                                                             ProjectKeys::KEY_METADATA_SUBSET,
                                                             ProjectKeys::PROJECT_TYPE_DIR]
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
}