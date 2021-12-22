<?php
/**
 * Class ListProjectsAction: Mostra en una pàgina el resultat de la selecció de projectes
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();
include_once(DOKU_INC.'/inc/form.php');

class ListProjectsAction extends AdminAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        /**
         * Informa si, en les dades del projecte, el camp 'field' conté el valor 'value'
         * @param array $dades : array de dades del projecte
         * @param array $params : ['field', 'value']
         * @return boolean
         */
        $function = function($dades, $params) {
                        $field = $params[0];
                        $value = $params[1];
                        return (is_array($dades) && !empty($dades[$field]));
                    };
        $callback = ['function' => $function,
                     'params' => explode(":", $this->params['consulta'])];
        
        $llista = $this->getModel()->selectProjectsByField($this->params['projectType'], $callback);
        $response = ['id' => $this->params[AjaxKeys::KEY_ID],
                     'title' => "Llista de projectes seleccionats i filtrats",
                     'content' => $this->setSelectedProjectsList($llista),
                     'type' => "html_form"
                    ];
        return $response;
    }

    private function setSelectedProjectsList($llista="") {
        $html = '<h1 class="sectionedit1" id="'.$this->params[AjaxKeys::KEY_ID].'">Lista de projectes seleccionats</h1>'
               .'<div class="level1"><p>Lista de projectes seleccionats amb condicions específiques</p></div>'
               .'<div style="padding:10px; width:50%;"><ul>';
        foreach ($llista as $elem) {
            $html .= "<li><a href='lib/exe/ioc_ajax.php?call=project&do=view&id=$elem'>$elem</a></li>";
        }
        $html .= "</ul></div>";
        return $html;
    }

}
