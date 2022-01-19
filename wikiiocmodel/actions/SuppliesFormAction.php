<?php
/**
 * Class SuppliesFormAction: Crea una pàgina amb un formulari per seleccionar els projectes
 *                           d'un tipus i una propietat específics.
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();
include_once(DOKU_INC.'/inc/form.php');

class SuppliesFormAction extends AdminAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        if ($this->params['do']['consulta']) {
            $command = "select_projects";
        }else {
            $command = $this->params[AjaxKeys::KEY_ID];
        }
        $content = $this->creaForm();

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ID],
                           PageKeys::KEY_TITLE => "Selecció de projectes",
                           PageKeys::KEY_CONTENT => $content,
                           AjaxKeys::KEY_ACTION_COMMAND => $command,
                           PageKeys::KEY_TYPE => "html_supplies_form"
                          ];
        return $this->response;
    }

    /** Construeix un formulari a partir d'un arbre d'elements rebut del client */
    protected function creaForm() {
        $ret = [];
        $ret['formId'] = $formId = "dw__{$this->params[AjaxKeys::KEY_ID]}";
        $ret['list'] = '<h1 class="sectionedit1" id="id_'.$this->params[AjaxKeys::KEY_ID].'">Selecció de projectes</h1>'
                      .'<div class="level1"><p>Selecciona el tipus de projecte i les condicions de cerca.</p></div>'
                      .'<div style="text-align:left; padding:10px; width:35%; border:1px solid gray">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);

        //FILTRE
        $attrs = ['_text' => "Filtre pels tipus de projecte:&nbsp;",
                  'name' => "filtre",
                  'type' => "text",
                  'size' => "18",
                  'value' => $this->params['filtre']];
        $this->_creaElement($form, $attrs);
        $this->_creaBoto($form, "filtre", "filtre", ['id'=>'btn__filtre', 'tabindex'=>'1']);
        $form->addElement("<p></p>");

        //LLISTA DE TIPUS DE PROJECTE
        $ret['grups']['grup_tipusprojecte'] = ['connector'=>"",
                                               'elements'=>[]];
        $aListProjectTypes = $this->getListPtypes($this->params['filtre']);
        $attrs = ['_text' => "Tipus de projecte:&nbsp;",
                  'name' => "projectType"];
        //$attrs['_options'][] = ["", "", "", false]; //'value','text','select','disabled' (primer elemento nulo)
        foreach ($aListProjectTypes as $v) {
            $attrs['_options'][] = [$v['id'],$v['name'],"",false]; //'value','text','select','disabled'
        }
        $this->_obreSpan($form);
        $form->addElement(form_listboxfield($attrs));
        $form->addElement("</span>");
        $this->_creaConnectorGrup($form, $ret['grups'], "T");
        $form->addElement("<p></p>");

        //CONDICIONS - Anàlisi de l'arbre
        if ($this->params['params']) {
            $this->_creaArbre($form, $ret['grups']);
        }else {
            $ret['grups']['grup0'] = ['connector'=>"",
                                      'elements'=>[]];
            $this->_creaElement($form, "", "0");
            $this->_creaConnectorGrup($form, $ret['grups'], "0");
            $this->_creaBotoNouElement($form, "0");
            $form->addElement("<p></p>");
        }

        //BOTÓ CERCA
        $this->_creaBoto($form, "cerca", WikiIocLangManager::getLang('btn_search'), ['form' => $formId]);

        $ret['list'] .= $form->getForm();
        $ret['list'] .= "</div> ";
        return $ret;
    }

    /* Construeix un formulari amb un element Select que conté la llista de tipus de projecte
     * i un element Text per a la construcció d'un filtre basat en un atribut (camp de l'array de dades)
     */
    private function setFormProjectTypes($filtre="") {
        $ret = [];
        $ret['formId'] = $formId = "dw__{$this->params[AjaxKeys::KEY_ID]}";
        $ret['list'] = '<h1 class="sectionedit1" id="id_'.$this->params[AjaxKeys::KEY_ID].'">Selecció de projectes</h1>'
                      .'<div class="level1"><p>Selecciona el tipus de projecte i les condicions de cerca.</p></div>'
                      .'<div style="text-align:left; padding:10px; width:35%; border:1px solid gray">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);

        //FILTRE
        $attrs = ['_text' => "Filtre pels tipus de projecte:&nbsp;",
                  'name' => "filtre",
                  'type' => "text",
                  'size' => "18",
                  'value' => $filtre];
        $this->_obreSpan($form);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");
        $this->_obreSpan($form);
        $button = form_makeButton('submit', "filtre", "filtre", ['id'=>'btn__filtre', 'tabindex'=>'1']);
        $form->addElement(form_button($button));
        $form->addElement("</span>");
        $form->addElement("<p></p>");

        //LLISTA DE TIPUS DE PROJECTE
        $aListProjectTypes = $this->getListPtypes($filtre);
        $attrs = ['_text' => "Tipus de projecte:&nbsp;",
                  'name' => "projectType"];
        //$attrs['_options'][] = ["", "", "", false]; //'value','text','select','disabled' (primer elemento nulo)
        foreach ($aListProjectTypes as $v) {
            $attrs['_options'][] = [$v['id'],$v['name'],"",false]; //'value','text','select','disabled'
        }
        $this->_obreSpan($form);
        $form->addElement(form_listboxfield($attrs));
        $form->addElement("</span>");

        $this->_creaConnectorGrup($form, $ret['grups'], "0");
        $form->addElement("<p></p>");
        
        //CONSULTA
        $attrs = ['_text' => "condicions:&nbsp;",
                  'name' => "consulta",
                  'type' => "text",
                  'size' => "35",
                  'value' => ""];
        $this->_obreSpan($form);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");

        $this->_creaBotoNouElement($form);

        $form->addElement("<p></p>");

        //BOTÓ CERCA
        $button = form_makeButton('submit', "cerca", WikiIocLangManager::getLang('btn_search'), ['form' => $formId]);
        $form->addElement("<div style='margin-top:25px;'>");
        $form->addElement(form_button($button));
        $form->addElement("</div>");

        $ret['list'] .= $form->getForm();
        $ret['list'] .= "</div> ";
        return $ret;
    }

    // Construeix els elements HTML a partir de l'arbre
    private function _creaArbre(&$form, &$ret) {
        foreach ($this->params['params'] as $g => $grup) {
            foreach ($grup as $key => $value) {
                if ($key == "conector") {
                    $this->_creaConnectorGrup($form, $ret, $g);
                }else {
                    $this->_creaElement($form, $value, $g);
                }
            }
            $this->_creaBotoNouElement($form, $g);
            $form->addElement("<p></p>");
        }
        
    }

    private function _creaElement(&$form, $value="", $grup=0) {
        if (is_array($value)) {
            $attrs = $value;
        }else {
            $attrs = ['_text' => "condicions:&nbsp;",
                      'name' => "consulta_grup_$grup",
                      'type' => "text",
                      'size' => "35",
                      'value' => $value];
        }
        $this->_obreSpan($form);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");
    }

    private function _creaBotoNouElement(&$form, $grup=0) {
        $this->_creaBoto($form, "nou_element_grup_$grup", "nou", ['id'=>"btn__nou_element_grup_$grup"]);
    }

    private function _creaConnectorGrup(&$form, &$ret, $grup) {
        $values = ['nul' => "",
                   'and' => "I",
                   'or' => "O"];
        $valor = $this->params['params']['grups']["grup_$grup"];
        $connector = form_makeMenuField("grup_$grup", $values, $valor, "", "connector:", "idconnector_$grup");
        $this->_obreSpan($form);
        $form->addElement(form_menufield($connector));
        $form->addElement("</span>");
        $ret["grup_$grup"]["connector"] = $valor;
    }

    private function _creaBoto(&$form, $action, $title='', $attrs=array(), $type='submit') {
        $this->_obreSpan($form);
        $button = form_makeButton($type, $action, $title, $attrs);
        $form->addElement(form_button($button));
        $form->addElement("</span>");
    }

    private function _obreSpan(&$form) {
        $form->addElement("<span style='margin:0 20px 10px 0;'>");
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
        $aList = array_column($aList, NULL);
        return $aList;
    }

    static private function ordena($a, $b) {
        return ($a['name'] > $b['name']) ? 1 : (($a['name'] < $b['name']) ? -1 : 0);
    }

}
