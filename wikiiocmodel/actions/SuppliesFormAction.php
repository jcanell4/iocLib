<?php
/**
 * Class SuppliesFormAction: Crea una pàgina amb un formulari per seleccionar els projectes
 *                           d'un tipus i una propietat específics.
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();
include_once(DOKU_INC.'/inc/form.php');

class SuppliesFormAction extends AdminAction {

    const DIVGRUP = '<div style="text-align:left; margin:7px; padding:10px; border:1px solid blue; border-radius:8px;">';
    const OBRE_SPAN = '<span style="margin:0 20px 10px 0;">';

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        if (isset($this->params['do']['cerca'])) {
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
                      .'<div style="text-align:center; padding:10px; width:70%; border:1px solid gray; border-radius:10px;">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);

        $form->addElement(self::DIVGRUP);
        //FILTRE
        $attrs = ['_text' => "Filtre pels tipus de projecte:&nbsp;",
                  'name' => "filtre",
                  'type' => "text",
                  'size' => "18",
                  'value' => $this->params['filtre']];
        $this->_creaElement($form, $ret['grups'], IocCommon::nz($this->params["filtre"], ""), $attrs, "F");
        $this->_creaBotó($form, "filtre", "filtre", ['id'=>'btn__filtre', 'tabindex'=>'1']);
        $form->addElement("<p></p>");

        //LLISTA DE TIPUS DE PROJECTE
        $aListProjectTypes = $this->getListPtypes($this->params['filtre']);
        $attrs = ['_text' => "Tipus de projecte:&nbsp;",
                  'name' => "projectType"];
        foreach ($aListProjectTypes as $v) {
            $attrs['_options'][] = [$v['id'],$v['name'],"",false]; //'value','text','select','disabled'
        }
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_listboxfield($attrs));
        $form->addElement("</span>");
        $ret['grups']['grup_T']['elements'][] = $this->params[AjaxKeys::PROJECT_TYPE];
        $this->_creaConnectorGrup($form, $ret['grups'], $this->params['connector_grup_T'], "T");
        $form->addElement("</div>");

        //GRUPS
        //Botó de creació d'un grup de primer nivell
        $form->addElement("<p>&nbsp;</p><p style='text-align:right;'>");
        $this->_creaBotóNouGrup($form);
        $form->addElement("</p>");

        $last_group = "0";

        if (!isset($this->params['grups'])) {
            //Estat inicial
            $this->_creaGrup($form, $ret, $last_group);
            $this->_creaCondició($form, $ret['grups'], "0", "", $last_group);
        }
        else {
            //Arbre de GRUPS
            $grups = json_decode($this->params['grups'], true);
            $last_group = $grups['last_group'];

            //S'ha pulsat el botó [nou Grup]
            if (isset($this->params['do']["nou_grup"])) {
                $last_group++;
                $grups["grup_$last_group"] = ['connector' => "",
                                              'elements' => [""]];
            }

            //Elements de CONDICIONS
            for ($i=0; $i<100; $i++) {
                if (isset($this->params['do']["nova_condicio_grup_$i"])) {
                    //S'ha pulsat el botó [nova Condició]
                    $grups["grup_$i"]['elements'][] = "";
                    break;
                }
            }

            //Recontrueix el formulari a partir de l'arbre
            $this->_recreaArbre($form, $ret['grups'], $grups);
            $grups['last_group'] = $last_group;
        }

        $form->addElement("</div>");

        //BOTÓ CERCA
        $form->addElement("<p>&nbsp;</p>");
        $this->_creaBotó($form, "cerca", WikiIocLangManager::getLang('btn_search'), ['form' => $formId]);

        $form->addHidden('grups', json_encode($ret['grups']));

        $ret['list'] .= $form->getForm();
        $ret['list'] .= "</div> ";
        return $ret;
    }

    // Reconstrueix els elements HTML a partir de l'arbre
    private function _recreaArbre(&$form, &$ret, $grups) {
        foreach ($grups as $G => $grup) {
            $g = explode("_", $G)[1];
            if (is_numeric($g)) {
                $this->_creaGrup($form, $ret, $this->params["connector_grup_$g"], $g);
                foreach ($grup as $key => $value) {
                    if ($key == "elements") {
                        foreach ($value as $k => $element) {
                            $this->_creaPreCondició($form, $ret, $k, $this->params["condicio_${k}_grup_$g"], $g);
                            $form->addElement("<p></p>");
                        }
                    }
                }
                $form->addElement("</div>");
            }
        }
    }

    private function _creaCondició(&$form, &$ret, $n="0", $value="", $grup="0") {
        $this->_creaPreCondició($form, $ret, $n, $value, $grup);
        $form->addElement("<p></p>");
        $ret['last_group'] = $grup;
    }

    private function _creaPreCondició(&$form, &$ret, $n="0", $value="", $grup="0") {
        $value = IocCommon::nz($value, "");
        $attrs = ['_text' => "condició ${n}:&nbsp;",
                  'name' => "condicio_${n}_grup_${grup}",
                  'type' => "text",
                  'size' => "35",
                  'value' => $value];
        $this->_creaElement($form, $ret, $value, $attrs, $grup);
    }

    private function _creaGrup(&$form, &$ret, $valor_connector="", $grup="0") {
        $form->addElement(self::DIVGRUP);
        $form->addElement("<div style='float:left;margin:0 0 10px 0;'><b>Grup $grup</b></div>");
        $form->addElement('<div style="float:right;text-align:right;margin:0 0 10px 0;">');
        $this->_creaBotóNovaCondició($form, $grup);
        $form->addElement('</div>');
        $form->addElement("<div style='clear:left;text-align:left;margin:0 0 10px 0;'>connector:&nbsp;");
        $this->_creaConnectorGrup($form, $ret, $valor_connector, $grup);
        $form->addElement('</div>');
    }

    private function _creaElement(&$form, &$ret, $value, $attrs, $grup="0") {
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");
        $ret["grup_$grup"]['elements'][] = $value;
    }

    private function _creaBotóNovaCondició(&$form, $grup="0") {
        $this->_creaBotó($form, "nova_condicio_grup_$grup", "nova Condició", ['id'=>"btn__nova_condicio_grup_$grup"]);
    }

    private function _creaBotóNouSubGrup(&$form, $grup="0") {
        $this->_creaBotó($form, "nou_subgrup_$grup", "nou SubGrup", ['id'=>"btn__nou_subgrup_$grup"]);
    }

    private function _creaBotóNouGrup(&$form) {
        $this->_creaBotó($form, "nou_grup", "nou Grup", ['id'=>"btn__nou_grup"]);
    }

    private function _creaConnectorGrup(&$form, &$ret, $valor="", $grup="0") {
        $valor = IocCommon::nz($valor, "");
        $values = ['' => "",
                   'and' => "I",
                   'or' => "O"];
        $connector = form_makeMenuField("connector_grup_$grup", $values, $valor, "", "connector:", "idconnector_$grup");
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_menufield($connector));
        $form->addElement("</span>");
        $ret["grup_$grup"]["connector"] = $valor;
    }

    private function _creaBotó(&$form, $action, $title='', $attrs=array(), $type='submit') {
        $button = form_makeButton($type, $action, $title, $attrs);
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_button($button));
        $form->addElement("</span>");
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

    /* Construeix un formulari amb un element Select que conté la llista de tipus de projecte
     * i un element Text per a la construcció d'un filtre basat en un atribut (camp de l'array de dades)
     */
    /*
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
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");
        $form->addElement(self::OBRE_SPAN);
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
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_listboxfield($attrs));
        $form->addElement("</span>");

        $this->_creaConnectorGrup($form, $ret['grups'], "0");
        $form->addElement("<p></p>");

        //CONSULTA
        $attrs = ['_text' => "condicions:&nbsp;",
                  'name' => "condicio",
                  'type' => "text",
                  'size' => "35",
                  'value' => ""];
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");

        $this->_creaBotóNovaCondició($form);

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
    */
}
