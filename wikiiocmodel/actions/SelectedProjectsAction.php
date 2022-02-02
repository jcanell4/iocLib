<?php
/**
 * Class SelectedProjectsAction: Crea una pàgina amb un formulari per seleccionar els projectes
 *                             d'un tipus i una propietat específics.
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();
include_once(DOKU_INC.'/inc/form.php');

class SelectedProjectsAction extends AdminAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        $model = $this->getModel();
        /**
         * Informa si en les dades del projecte el camp 'field' conté el valor 'value'
         * @param array $dades : array de dades del projecte
         * @param array $params : ['field', 'value']
         * @return boolean
         */
//        $function = function($dades, $params) {
//                        $field = $params[0];
//                        $value = $params[1];
//                        return (is_array($dades) && !empty($dades[$field]));
//                    };
//        $callback = ['function' => $function,
//                     'params' => explode(":", $this->params['consulta'])];
//
//        $llista = $model->selectProjectsByField($this->params['projectType'], $callback);
        $llista = $this->parser($this->params['grups']);

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ACTION_COMMAND],
                           PageKeys::KEY_TITLE => "Llista de projectes seleccionats i filtrats",
                           PageKeys::KEY_CONTENT => $this->setSelectedProjectsList($llista['listProjects']),
                           PageKeys::KEY_TYPE => "html_response_form"
                          ];
        return $this->response;
    }

    private function parser($G) {
        $listProjects = [];
        $grups = (is_string($G)) ? json_decode($G, true) : $G;
        $mainGroup = $grups["grup_${grups['main_group']}"];
        foreach ($grups as $key => $grup) {
            if (preg_match("/grup_(.*)/", $key, $g)) {
                if ($grup['projecttype']) {
                    $listProjects[] = $grup['projecttype'];
                }
            }else {
                unset($grups[$key]);
            }
        }
        return ['mainGroup' => $mainGroup, 'grups' => $grups, 'listProjects' => $listProjects];
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
