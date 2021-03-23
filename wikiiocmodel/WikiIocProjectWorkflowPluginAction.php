<?php
/**
 * WikiIocProjectWorkflowPluginAction: compila les propietats dels botons de projecte definides als arxius de control
 * @culpable Rafael Claver <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class WikiIocProjectWorkflowPluginAction extends WikiIocProjectPluginAction {

    private $workflowArray;

    public function __construct($projectType, $dirProjectType) {
        parent::__construct($projectType, $dirProjectType);
        $this->workflowArray = $this->projectMetaDataQuery->getMetaViewConfig("workflow", $projectType);
    }

    function addControlScripts(Doku_Event &$event, $param) {
        if ($this->workflowArray) {
            foreach ($this->workflowArray as $state => $arrayStates) {
                $wArray = $this->creaArrayButtons($state, $arrayStates);
                if (!empty($wArray)) {
                    $this->p_addControlScripts($event, $wArray);
                }
            }
        }
    }

    function addWikiIocButtons(Doku_Event &$event, $param) {
        if ($this->workflowArray) {
            foreach ($this->workflowArray as $state => $arrayStates) {
                $wArray = $this->creaArrayButtons($state, $arrayStates);
                if (!empty($wArray)) {
                    $this->p_addWikiIocButtons($event, $wArray);
                }
            }
        }
    }

    private function creaArrayButtons($state, $arrayStates) {
        $wArray = array();
        foreach ($arrayStates as $arrayActions) {
            foreach ($arrayActions as $name => $action) {
                if (($shortcut = $action['shortcut'])) {
                    $sh_action = $this->workflowArray[$shortcut]['actions'][$name];
                    $button = ($action['button']) ? $action['button'] : $sh_action['button'];
                    $permissions = ($action['permissions']) ? $action['permissions'] : $sh_action['permissions'];
                }else {
                    $button = $action['button'];
                    $permissions = $action['permissions'];
                }
                if ($button) {
                    if (isset($button['id'])) {
                        $id = str_replace("Button", "", $button['id']);
                    }elseif (isset($button['parms']['DOM']['id'])) {
                        $id = str_replace("Button", "", $button['parms']['DOM']['id']);
                    }
                    if ($id) {
                        if (!isset($button['class']) || isset($button['toSet']) || isset($button['toDelete'])) {
                            $button['overwrite'] = TRUE;
                        }
                        foreach ($permissions['groups'] as $k => $value) {
                            if (!preg_match("/^is.*/", $value)) {
                                $permissions['groups'][$k] = "is$value";
                            }
                        }
                        $button['scripts']['updateHandler']['permissions'] = $permissions['groups'];
                        $button['scripts']['updateHandler']['rols'] = $permissions['rols'];
                        //$button['scripts']['updateHandler']['conditions']['page.workflowState'] = "'$state'";  //NO eliminar. Serveix d'exemple
                        $button['scripts']['updateHandler']['processCondition']['page.workflowState'] = "$state";
                        $wArray["$state$id"] = $button;
                    }
                }
            }
        }
        return $wArray;
    }
}
