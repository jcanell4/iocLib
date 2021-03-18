<?php
/**
 * WikiIocPluginAction: classe base de les classes action de plugins de projectes
 * @culpable Rafael Claver
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . "lib/plugins/");
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_PLUGIN . "wikiiocmodel/");

class WikiIocProjectPluginAction extends WikiIocPluginAction {

    protected $dirProjectType;
    private $viewArray;

    public function __construct($projectType, $dirProjectType) {
        parent::__construct();
        $this->projectType = $projectType;
        $this->dirProjectType = $dirProjectType;
        $this->viewArray = $this->projectMetaDataQuery->getMetaViewConfig("controls", $projectType);
    }

    function addControlScripts(Doku_Event &$event, $param) {
        $this->p_addControlScripts($event, $this->viewArray);
    }

    protected function p_addControlScripts(Doku_Event &$event, $wArray) {
        $changeWidgetPropertyFalse = "";
        $changeWidgetPropertyCondition = "";
        $VarsIsButtonVisible = "";
        $permissionsButtonVisible = "";
        $conditionsButtonVisible = "";
        $path = "{$this->dirProjectType}metadata/config/";
        $counter = 0;

        //Lectura de los botones definidos en el fichero de control
        foreach ($wArray as $arrayButton) {
            if ($arrayButton['scripts']) {
                $counter++;
                if ($arrayButton['scripts']['getFunctions']) {
                    //carga de los archivos de funciones de los botones
                    foreach ($arrayButton['scripts']['getFunctions'] as $key => $value) {
                        if ($key === "path")
                            $event->data->addControlScript($path.$value);
                    }
                }

                if (isset($arrayButton['id'])) {
                    $id = $arrayButton['id'];
                }else {
                    $id = $arrayButton['parms']['DOM']['id'];
                }
                //Construcción de los valores de sustitución de los patrones para el template UpdateViewHandler
                //changeWidgetProperty sólo para los botones propios
                if (!(!isset($arrayButton['class']) || isset($arrayButton['overwrite']))) {
                    $changeWidgetPropertyFalse .= "disp.initUpdateWidgetProperty('${id}', 'visible', false);\n\t\t\t";
                }
                $changeWidgetPropertyCondition .= "disp.changeWidgetProperty('${id}', 'visible', is${id}ButtonVisible);\n\t\t\t\t\t";
                $VarsIsButtonVisible .= "var is${id}ButtonVisible = true;\n\t\t\t\t\t";

                //Obtener los permisos y roles por defecto del Authorization correspondiente
                if ($arrayButton['scripts']['updateHandler']['command_authorization']) {
                    $modelManager = AbstractModelManager::Instance($this->projectType);
                    $buttonAuthorization = $modelManager->getAuthorizationManager($arrayButton['scripts']['updateHandler']['command_authorization']);
                    if(!empty($buttonAuthorization->getAllowedGroups())){
                        $aPermissions =[];
                        foreach ($buttonAuthorization->getAllowedGroups() as $value) {
                            $aPermissions[] = "is$value";
                        }
                    }
                    $aRoles = $buttonAuthorization->getAllowedRoles();
                }

                //bucle para que los permisos determinen si el botón correspondiente es visible u oculto
                $permButtonVisible = "";
                if ($arrayButton['scripts']['updateHandler']['permissions']) {
                    //los permisos de controls.json sustituyen a los permisos por defecto del Authorization
                    $aPermissions = $arrayButton['scripts']['updateHandler']['permissions'];
                }
                if ($aPermissions) {
                    $permButtonVisible = "is${id}ButtonVisible = (";
                    foreach ($aPermissions as $value) {
                        $permButtonVisible .= "disp.getGlobalState().permissions['$value'] || ";
                    }
                    $permButtonVisible = substr($permButtonVisible, 0, -4) . ");";
                }
                $permissionsButtonVisible .= $permButtonVisible . "\n\t\t\t\t\t\t\t";

                //bucle para que los roles determinen si el botón correspondiente es visible u oculto
                $rolButtonVisible = "";
                if ($arrayButton['scripts']['updateHandler']['rols']) {
                    //los roles de controls.json sustituyen a los roles por defecto del Authorization
                    $aRoles = $arrayButton['scripts']['updateHandler']['rols'];
                }
                if ($aRoles) {
                    $rolButtonVisible = "is${id}ButtonVisible = is${id}ButtonVisible || (";
                    foreach ($aRoles as $value) {
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
                        $values = explode("|", $value);
                        if(count($values)>1){
                            $startStr = "(";
                            $glue = "|| ";
                            $endStr = ")";
                        }else{
                            $startStr = $glue = $endStr = "";
                        }
                        $condButtonVisible .= "$startStr";
                        foreach ($values as $item) {
                            $condButtonVisible .= "$key==$item$glue";                        
                        }
                        if(!empty($glue)){
                            $condButtonVisible = substr($condButtonVisible, 0, -3);
                        }
                        $condButtonVisible .= "$endStr && ";
                        
                    }
                    $condButtonVisible = substr($condButtonVisible, 0, -4) . ");";
                }
                $conditionsButtonVisible .= $condButtonVisible . "\n\t\t\t\t\t";
            }
        }

        if ($counter > 0) {
            $aReplacements["search"] = ["//%_changeWidgetPropertyFalse_%",
                                        "%_projectType_%",
                                        "//%_VarsIsButtonVisible_%",
                                        "//%_permissionButtonVisible_%",
                                        "//%_rolesButtonVisible_%",
                                        "//%_conditionsButtonVisible_%",
                                        "//%_changeWidgetPropertyCondition_%"];
            $aReplacements["replace"] = [$changeWidgetPropertyFalse,
                                         $this->projectType,
                                         $VarsIsButtonVisible,
                                         $permissionsButtonVisible,
                                         $rolesButtonVisible,
                                         $conditionsButtonVisible,
                                         $changeWidgetPropertyCondition];

            $arxiu =  WIKI_IOC_MODEL."metadata/templates/templateUpdateViewHandler.js";
            $event->data->addControlScript($arxiu, $aReplacements);
        }
    }

    function addWikiIocButtons(Doku_Event &$event, $param) {
        $this->p_addWikiIocButtons($event, $this->viewArray);
    }

    protected function p_addWikiIocButtons(Doku_Event &$event, $wArray) {
        //Lectura de los botones definidos en el fichero de control
        $configDataFunctions = array();
        foreach ($wArray as $arrayButton) {
            $button = array();
            if (isset($arrayButton['class'])) {
                $class = $arrayButton['class'];
                if ($arrayButton['parms']['DOM']) $button['DOM'] = $arrayButton['parms']['DOM'];
                if ($arrayButton['parms']['DJO']) $button['DJO'] = $arrayButton['parms']['DJO'];
                if ($arrayButton['parms']['CSS']) $button['CSS'] = $arrayButton['parms']['CSS'];
                if ($arrayButton['parms']['PRP']) $button['PRP'] = $arrayButton['parms']['PRP'];
                $event->data->addWikiIocButton($class, $button);
            } elseif(isset($arrayButton['toDelete']) || isset($arrayButton['toSet'])) {
                $configDataFunctions[] = $this->addOverwritingFunctions($arrayButton);
            }
        }
        if (count($configDataFunctions) > 0 ) {
            $aReplacements["search"] = ["%_projectType_%",
                                        "___JSON_BUTTON_ATTRIBUTES_DATA___"];
            $aReplacements["replace"] = [$this->projectType,
                                         json_encode($configDataFunctions)];

            $arxiu =  WIKI_IOC_MODEL."metadata/templates/templateUpdateButtonAttributes.js";
            $event->data->addControlScript($arxiu, $aReplacements);
        }
    }

    protected function addOverwritingFunctions($arrayButton) {
        $configDataFunctionsItem = array();
        if (isset($arrayButton['id'])) {
            $configDataFunctionsItem["id"] = $arrayButton['id'];
        }else {
            $configDataFunctionsItem["id"] = $arrayButton['parms']['DOM']['id'];
        }

        if (isset($arrayButton['toSet'])) {
            $configDataFunctionsItem["toSet"] = $arrayButton['toSet'];
        }elseif(isset($arrayButton['parms']['toSet'])) {
             $configDataFunctionsItem["toSet"] = $arrayButton['parms']['toSet'];
        }
        if (isset($arrayButton['toDelete'])) {
            $configDataFunctionsItem["toDelete"] = $arrayButton['toDelete'];
        }elseif(isset($arrayButton['parms']['toDelete'])) {
            $configDataFunctionsItem["toDelete"] = $arrayButton['parms']['toDelete'];
        }
        return $configDataFunctionsItem;
    }

}
