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
        $wArray = $this->creaArrayButtons();
        if (!empty($wArray)) {
            $this->p_addControlScripts($event, $wArray);
        }
    }

    function addWikiIocButtons(Doku_Event &$event, $param) {
        $wArray = $this->creaArrayButtons();
        if (!empty($wArray)) {
            $this->p_addWikiIocButtons($event, $wArray);
        }
    }

    private function creaArrayButtons() {
        $wArray = array();
        foreach ($this->workflowArray as $state => $arrayStates) {
            foreach ($arrayStates as $arrayActions) {
                foreach ($arrayActions as $name => $action) {
                    if (($shortcut = $action['shortcut'])) {
                        $action = $this->workflowArray[$shortcut]['actions'][$name];
                    }
                    if ($action['button']) {
                        if (isset($action['button']['id'])) {
                            $id = str_replace("Button", "", $action['button']['id']);
                        }elseif (isset($action['button']['parms']['DOM']['id'])) {
                            $id = str_replace("Button", "", $action['button']['parms']['DOM']['id']);
                        }
                        if ($id) {
                            if (!isset($action['button']['class']) || isset($action['button']['toSet']) || isset($action['button']['toDelete'])) {
                                $action['button']['overwrite'] = TRUE;
                            }
                            $action['button']['scripts']['updateHandler']['permissions'] = $action['permissions']['groups'];
                            $action['button']['scripts']['updateHandler']['rols'] = $action['permissions']['rols'];
                            $estat = ($shortcut) ? $shortcut : $state;
                            $action['button']['scripts']['updateHandler']['conditions']['page.workflowState'] = "'$estat'";
                            $wArray[$id] = $action['button'];
                        }
                    }
                }
            }
        }
        return $wArray;
    }
}
