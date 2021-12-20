<?php
/**
 * Class SelectProjectsAction: Crea una pàgina amb un formulari per seleccionar els projectes
 *                             d'un tipus i una propietat específics.
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();
include_once(DOKU_INC.'/inc/form.php');

class SelectProjectsAction extends AdminAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        if ($this->params['projectType']) {
            $this->response = $this->showSelectedProjects();
        }else {
            $this->response = $this->createForm();
        }
        return $this->response;
    }

    protected function showSelectedProjects() {
        $model = $this->getModel();
        /**
         * Informa si en les dades del projecte el camp 'field' conté el valor 'value'
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
        
        $llista = $model->getProjects($this->params['projectType'], $callback);
        $ret = ['id' => $this->params[AjaxKeys::KEY_ID],
                'title' => "Llista de projectes seleccionats i filtrats",
                'content' => $this->setSelectedProjectsList($llista),
                'type' => "html_form"
               ];
        return $ret;
    }

    private function setSelectedProjectsList($llista="") {
        $html = '<h1 class="sectionedit1" id="'.$this->params[AjaxKeys::KEY_ID].'">Lista de projectes seleccionats</h1>'
               .'<div class="level1"><p>Lista de projectes seleccionats amb condicions específiques</p></div>'
               .'<div style="padding:10px; width:50%;"><ul>';
        foreach ($llista as $elem) {
            $html .= "<li>$elem</li>";
        }
        $html .= "</ul></div>";
        return $html;
    }

    protected function createForm() {
        $ret = ['id' => $this->params[AjaxKeys::KEY_ID],
                'title' => "Selecció de projectes",
                'content' => $this->setFormProjectTypes(),
                'type' => "html_form"
               ];
        return $ret;
    }

    /* Construeix un formulari amb un element Select que conté la llista de tipus de projecte
     * i un element Text per a la construcció d'un filtre basat en un atribut (camp de l'array de dades)
     */
    private function setFormProjectTypes() {
        $ret = [];
        $ret['formId'] = $formId = "dw__{$this->params[AjaxKeys::KEY_ID]}";
        $ret['list'] = '<h1 class="sectionedit1" id="id_'.$this->params[AjaxKeys::KEY_ID].'">Selecció de projectes</h1>'
                      .'<div class="level1"><p>Selecciona el tipus de projecte i un atribut.</p></div>'
                      .'<div style="text-align:center; padding:10px; width:30%; border:1px solid gray">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);

        $aListProjectTypes = $this->getListPtypes("pt.*");

        $attrs = ['_text' => "Llista de tipus de projecte:&nbsp;",
                  'name' => "projectType"];
        foreach ($aListProjectTypes as $v) {
            $attrs['_options'][] = [$v['id'],$v['name'],"",false]; //'value','text','select','disabled'
        }
        $form->addElement(form_listboxfield($attrs));
        $form->addElement("<p></p>");
        
        $attrs = ['_text' => "Definició d'atribut i valor:&nbsp;&nbsp;",
                  'name' => "consulta",
                  'type' => "text",
                  'size' => "35",
                  'value' => ""];
        $form->addElement(form_field($attrs));

        $form->addElement("<p></p>");
        $button = form_makeButton('submit', $this->params[AjaxKeys::KEY_ID], WikiIocLangManager::getLang('btn_search'), ['form' => $formId]);
        $form->addElement(form_button($button));

        $ret['list'] .= $form->getForm();
        $ret['list'] .= "</div> ";
        return $ret;
    }

    /**
     * Retorna un array que conté la llista de tipus de projecte vàlids
     */
    private function getListPtypes($all=false) {
        $model = $this->getModel();
        $listProjectTypes = $model->getListProjectTypes($all);
        if ($all===true) {
            $listProjectTypes[] = "wikipages";
        }elseif (!empty($all)) {
            $temp = [];
            foreach ($listProjectTypes as $value) {
                if (preg_match("/$all/", $value)) {
                    $temp[] = $value;
                }
            }
            $listProjectTypes = $temp;
        }
        sort($listProjectTypes);
        $aList = [];
        foreach ($listProjectTypes as $pTypes) {
            $name = WikiGlobalConfig::getConf("projectname_$pTypes");
            if ($name) {
                $aList[] = ['id' => "$pTypes", 'name' => $name];
            }else{
                $aList[] = ['id' => "$pTypes", 'name' => $pTypes];
            }
        }
        uasort($aList, "self::ordena");
        return $aList;
    }

    static private function ordena($a, $b) {
        return ($a['name'] > $b['name']) ? 1 : (($a['name'] < $b['name']) ? -1 : 0);
    }

}
