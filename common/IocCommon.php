<?php
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_COMMAND')) define('DOKU_COMMAND', DOKU_INC."lib/plugins/ajaxcommand/");
require_once (DOKU_INC . 'inc/pageutils.php');
require_once(DOKU_COMMAND.'defkeys/ResponseHandlerKeys.php');
require_once(DOKU_COMMAND.'defkeys/ProjectKeys.php');
require_once(DOKU_TPL_INCDIR.'conf/cfgIdConstants.php');

/**
 * Class ioc_common: Contiene funciones comunes
 * @culpable Rafael
 */
class IocCommon {
    
    public static function getCalculateFieldFromFunction($calcDefProp, $projectId, $values) {
        if (isset($calcDefProp)) {
            $className = $calcDefProp['class'];
            $calculator = new $className;
            if ($calculator) {
                switch ($calculator->getCalculatorTypeData()){
                    case "from_values":
                        $calculator->init($projectId);
                        $value = $calculator->calculate($values[$calcDefProp['data']]);
                        break;
                    default :
                        $value = $calculator->calculate($calcDefProp['data']);
                }
            }
        }
        return $value;
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
                if (!rmdir($directory)) {
                    $ret = FALSE;
                }
            }
            return $ret;
        }
    }
    
    public static function countRevisions($id, $media=false){
        $ret = 0;
        if ($media) {
            $fileName = mediaMetaFN($id, '.changes');
        } else {
            $fileName = metaFN($id, '.changes');
        }    
        if(@file_exists($filename)){
            $file = new \SplFileObject($fileName, 'r');
            $file->seek(PHP_INT_MAX);
            $ret = $file->key() - 1;
        }

        return $ret;         
    }
    
    public static function addControlScripts(Doku_Event &$event, $param, $aux) {
        $changeWidgetPropertyFalse = "";
        $changeWidgetPropertyCondition = "";
        $VarsIsButtonVisible = "";
        $permissionsButtonVisible = "";
        $conditionsButtonVisible = "";
        $path = "{$aux['dirProjectType']}metadata/config/";

        //Lectura de los botones definidos en el fichero de control
        foreach ($aux['viewArray'] as $arrayButton) {
            if ($arrayButton['scripts']['getFunctions']) {
                //carga de los archivos de funciones de los botones
                foreach ($arrayButton['scripts']['getFunctions'] as $key => $value) {
                    if ($key === "path")
                        $event->data->addControlScript($path.$value);
                }
            }

            $id = $arrayButton['parms']['DOM']['id'];
            //Construcción de los valores de sustitución de los patrones para el template UpdateViewHandler
            //changeWidgetProperty para todos los botones
            $changeWidgetPropertyFalse .= "disp.initUpdateWidgetProperty('${id}', 'visible', false);\n\t\t\t";
            $changeWidgetPropertyCondition .= "disp.changeWidgetProperty('${id}', 'visible', is${id}ButtonVisible);\n\t\t\t\t";
            $VarsIsButtonVisible .= "var is${id}ButtonVisible = true;\n\t\t\t\t\t";

            //bucle para que los permisos determinen si el botón correspondiente es visible u oculto
            $permButtonVisible = "";
            if ($arrayButton['scripts']['updateHandler']['permissions']) {
                $permButtonVisible = "is${id}ButtonVisible = (";
                foreach ($arrayButton['scripts']['updateHandler']['permissions'] as $value) {
                    $permButtonVisible .= "disp.getGlobalState().permissions['$value'] || ";
                }
                $permButtonVisible = substr($permButtonVisible, 0, -4) . ");";
            }
            $permissionsButtonVisible .= $permButtonVisible . "\n\t\t\t\t\t\t\t";

            //bucle para que los roles determinen si el botón correspondiente es visible u oculto
            $rolButtonVisible = "";
            if ($arrayButton['scripts']['updateHandler']['rols']) {
                $rolButtonVisible = "is${id}ButtonVisible = is${id}ButtonVisible || (";
                foreach ($arrayButton['scripts']['updateHandler']['rols'] as $value) {
                    $rolButtonVisible .= "page.rol=='".$value."' || ";
                }
                $rolButtonVisible = substr($rolButtonVisible, 0, -4) . ");";
            }
            $rolesButtonVisible .= $rolButtonVisible . "\n\t\t\t\t\t\t";

            //bucle para que otras condiciones determinen si el botón correspondiente es visible u oculto
            $condButtonVisible = "";
            if ($arrayButton['scripts']['updateHandler']['conditions']) {
                $condButtonVisible = "is${id}ButtonVisible = is${id}ButtonVisible && (";
                foreach ($arrayButton['scripts']['updateHandler']['conditions'] as $key => $value) {
                    $condButtonVisible .= "$key==$value && ";
                }
                $condButtonVisible = substr($condButtonVisible, 0, -4) . ");";
            }
            $conditionsButtonVisible .= $condButtonVisible . "\n\t\t\t\t\t\t";
        }

        $aReplacements["search"] = ["//%_changeWidgetPropertyFalse_%",
                                    "%_projectType_%",
                                    "//%_VarsIsButtonVisible_%",
                                    "//%_permissionButtonVisible_%",
                                    "//%_rolesButtonVisible_%",
                                    "//%_conditionsButtonVisible_%",
                                    "//%_changeWidgetPropertyCondition_%"];
        $aReplacements["replace"] = [$changeWidgetPropertyFalse,
                                     $aux['projectType'],
                                     $VarsIsButtonVisible,
                                     $permissionsButtonVisible,
                                     $rolesButtonVisible,
                                     $conditionsButtonVisible,
                                     $changeWidgetPropertyCondition];

        $arxiu =  WIKI_IOC_MODEL."metadata/templates/templateUpdateViewHandler.js";
        $event->data->addControlScript($arxiu, $aReplacements);
    }

    public static function addWikiIocButtons(Doku_Event &$event, $param, $aux) {
        //Lectura de los botones definidos en el fichero de control
        foreach ($aux['viewArray'] as $arrayButton) {
            $button = array();
            if(isset($arrayButton['class'])){
                $class = $arrayButton['class'];
                if ($arrayButton['parms']['DOM']) $button['DOM'] = $arrayButton['parms']['DOM'];
                if ($arrayButton['parms']['DJO']) $button['DJO'] = $arrayButton['parms']['DJO'];
                if ($arrayButton['parms']['CSS']) $button['CSS'] = $arrayButton['parms']['CSS'];
                if ($arrayButton['parms']['PRP']) $button['PRP'] = $arrayButton['parms']['PRP'];
                $event->data->addWikiIocButton($class, $button);
            }
        }
    }
}
    