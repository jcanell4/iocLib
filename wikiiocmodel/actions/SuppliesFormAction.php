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

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ID],
                           PageKeys::KEY_TITLE => "Selecció de projectes",
                           PageKeys::KEY_CONTENT => $this->setFormProjectTypes($this->params['filtre']),
                           AjaxKeys::KEY_ACTION_COMMAND => $command,
                           PageKeys::KEY_TYPE => "html_supplies_form"
                          ];
        return $this->response;
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

        $this->_creaConnectorGrup($form, "0");
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

    private function _creaBotoNouElement(&$form) {
        $this->_obreSpan($form);
        $button = form_makeButton('submit', "nou_element", "nou", ['id'=>'btn__nou']);
        $form->addElement(form_button($button));
        $form->addElement("</span>");
    }

    private function _creaConnectorGrup(&$form, $grup) {
        $values = ['nul' => "",
                   'and' => "I",
                   'or' => "O"];
        $connector = form_makeMenuField("grup_$grup", $values, "", "connector:", "idconnector_$grup");
        $this->_obreSpan($form);
        $form->addElement(form_menufield($connector));
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
