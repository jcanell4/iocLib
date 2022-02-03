<?php
/**
 * Class SelectedProjectsAction: Crea una pàgina amb la llista de projectes que compleixen
 *                               les condicions selccionades.
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class SelectedProjectsAction extends AdminAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        $model = $this->getModel();
        $parser = $this->parser($this->params['grups']);
        $listProjects = $model->selectProjectsByType($parser['listProjectTypes']);

        foreach ($listProjects as $project) {
            $data_main = $model->getDataProject($project['ns'], $project['projectType'], "main");
            $data_all = $model->getAllDataProject($project['ns'], $project['projectType']);
            $root = NodeFactory::getNode($parser['grups'], $parser['mainGroup'], $data_main, $data_all);
            if ($root->getValue()) {
                $llista[] = $project['ns'];
            }
        }

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ACTION_COMMAND],
                           PageKeys::KEY_TITLE => "Llista de projectes seleccionats i filtrats",
                           PageKeys::KEY_CONTENT => $this->setSelectedProjectsList($llista),
                           PageKeys::KEY_TYPE => "html_response_form"
                          ];
        return $this->response;
    }

    private function parser($G) {
        $listProjectTypes = [];
        $grups = (is_string($G)) ? json_decode($G, true) : $G;
        $mainGroup = "grup_${grups['main_group']}";
        foreach ($grups as $key => $grup) {
            if (preg_match("/grup_(.*)/", $key, $g)) {
                if ($grup['projecttype']) {
                    $listProjectTypes[] = $grup['projecttype'];
                }
            }else {
                unset($grups[$key]);
            }
        }
        return ['mainGroup' => $mainGroup, 'grups' => $grups, 'listProjectTypes' => $listProjectTypes];
    }

    private function setSelectedProjectsList($llista="") {
        $html = '<h1 class="sectionedit1" id=dw__"'.$this->params[AjaxKeys::KEY_ACTION_COMMAND].'">Llista de projectes seleccionats</h1>'
               .'<div class="level1"><p>Llista de projectes seleccionats amb condicions específiques</p></div>'
               .'<div style="padding:10px; width:50%;"><ul>';
        foreach ($llista as $elem) {
            $html .= "<li><a href='lib/exe/ioc_ajax.php?call=project&do=view&id=$elem'>$elem</a></li>";
        }
        $html .= "</ul></div>";
        return $html;
    }

}
