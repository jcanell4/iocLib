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
        foreach ($this->workflowArray as $arrayStates) {
            foreach ($arrayStates as $arrayActions) {
                foreach ($arrayActions as $action) {
                    if (isset($action['button'])) {
                        if (isset($action['button']['id'])) {
                            $id = str_replace("Button", "", $action['button']['id']);
                            $wArray[$id] = $action['button'];
                        }elseif (isset($action['button']['parms']['DOM']['id'])) {
                            $id = str_replace("Button", "", $action['button']['parms']['DOM']['id']);
                            $wArray[$id] = $action['button'];
                        }
                    }
                }
            }
        }
        return $wArray;
    }
}
