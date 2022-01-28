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
    const DIVGRUPNAME = '<div style="float:left;margin:0 0 10px 0;">';
    const DIVGRUPBOTO = '<div style="float:right;text-align:right;margin:0 0 10px 0;">';
    const DIVGRUPCONN = '<div style="clear:left;text-align:left;margin:0 0 10px 0;">connector:&nbsp;';
    const OBRE_SPAN = '<span style="margin:0 20px 10px 0;">';

    private $command;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        if (isset($this->params['do']['cerca'])) {
            $this->command = "select_projects";
            $title = "Llistat de projectes seleccionats";
            $type = "html_response_form";
        }else {
            $this->command = $this->params[AjaxKeys::KEY_ID];
            $title = "Selecció de projectes";
            $type = "html_supplies_form";
        }
        $content = $this->creaForm();

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ID],
                           PageKeys::KEY_TITLE => $title,
                           PageKeys::KEY_CONTENT => $content,
                           AjaxKeys::KEY_ACTION_COMMAND => $this->command,
                           PageKeys::KEY_TYPE => $type
                          ];
        return $this->response;
    }

    /** Construeix un formulari a partir d'un arbre d'elements rebut del client */
    protected function creaForm() {
        $ret = [];
        $ret['formId'] = $formId = "dw__{$this->params[AjaxKeys::KEY_ID]}";
        $ret['list'] = '<h1 class="sectionedit1" id="id_'.$this->params[AjaxKeys::KEY_ID].'">Selecció de projectes</h1>'
                      .'<div class="level1"><p>Selecciona les condicions per fer la cerca als projectes.</p></div>'
                      .'<div style="text-align:center; padding:10px; width:70%; border:1px solid gray; border-radius:10px;">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);

//        $this->_creaFiltre($form, $ret['grups']);

        //GRUPS
        $this->_creacioGestioDeGrups($form);
        $main_group = "0";
        $last_group = "0";
        $lastGgroup = "0";

        if (!isset($this->params['grups'])) {
            $this->_creacioGrupGInicial($form, $ret, $lastGgroup);
            $this->_creacioGrupInicial($form, $ret, $last_group);
        }
        else {
            //Arbre de GRUPS
            $grups = json_decode($this->params['grups'], true);
            $main_group = $grups['main_group'];
            $lastGgroup = $grups['lastGgroup'];
            $last_group = $grups['last_group'];

            $this->_tractamentBotoNouGrup($grups, $last_group);
            $this->_tractamentBotoNovaAgrupacio($grups, $lastGgroup);
//            $this->_creaEspaiConnexionsDeGrup($form, $ret);
            $this->_tractamentBotoNovaCondicio($grups);

            //Recontrueix el formulari a partir de l'arbre
            $this->_recreaArbre($form, $ret['grups'], $grups);
        }
        
        $ret['grups']['main_group'] = $main_group;
        $ret['grups']['lastGgroup'] = $lastGgroup;
        $ret['grups']['last_group'] = $last_group;
        $form->addHidden('grups', json_encode($ret['grups']));
        $form->addElement("</div>");

        //BOTÓ CERCA
        $form->addElement("<p>&nbsp;</p>");
        $this->_creaBoto($form, "cerca", WikiIocLangManager::getLang('btn_search'), ['form'=> $formId, 'data-call'=> $this->command]);

        $ret['list'] .= $form->getForm();
        $ret['list'] .= "</div> ";
        return $ret;
    }

    //Creació del grup de grups inicial
    private function _creacioGrupGInicial(&$form, &$ret, $lastGgroup) {
        $values = ['connector_grup' => $this->params["connector_grup_G_$lastGgroup"],
                   'elements_grup' => [""]];
        $this->_creaGGrup($form, $ret['grups'], "G_$lastGgroup", $values);
        $this->_creaGCondicio($form, $ret['grups'], $ret['grups'], "0", "", "G_$lastGgroup");
        $form->addElement("</div>");
    }

    //Creació del grup simple inicial
    private function _creacioGrupInicial(&$form, &$ret, $last_group) {
        $values = ['connector_grup' => $this->params["connector_grup_0"],
                   'projecttype_grup' => $this->params["projecttype_grup_0"]];
        $this->_creaGrup($form, $ret['grups'], $last_group, $values);
        $this->_creaCondicio($form, $ret['grups'], "0", "", $last_group);
        $form->addElement("<p></p>");
    }

    //Creació dels elements per a la gestió de grups
    private function _creacioGestioDeGrups(&$form) {
        $form->addElement("<p style='text-align:right;'>");
        $this->_creaBotoNouGrup($form);
        $form->addElement("</p>");
        $form->addElement("<p style='text-align:right;'>");
        $this->_creaBotoNovaAgrupacio($form);
        $form->addElement("</p>");
    }

    //S'ha pulsat el botó [nou Grup]
    private function _tractamentBotoNouGrup(&$grups, &$last_group) {
        if (isset($this->params['do']["nou_grup"])) {
            $last_group++;
            $grups["grup_$last_group"] = ['connector' => "",
                                          'projecttype' => "",
                                          'elements' => [""]];
        }
    }

    //S'ha pulsat el botó [nova Agrupació]
    private function _tractamentBotoNovaAgrupacio(&$grups, &$lastGgroup) {
        if (isset($this->params['do']["nova_agrupacio"])) {
            $lastGgroup++;
            $grups["grup_G_$lastGgroup"] = ['connector' => "",
                                            'elements' => [""]];
        }
    }

    //Creació d'un espai per mostrar les connexions de grup actualment establertes
    private function _creaEspaiConnexionsDeGrup(&$form, $ret) {
        if (!empty($ret['grups']["grup_G"])) {
            $form->addElement(self::DIVGRUP);
            $form->addElement("<p><b>Connexions de grups actualment establertes</b></p>");
            foreach ($ret['grups']["grup_G"] as $K => $G) {
                foreach ($G as $key => $connector) {
                    if ($key == 'connector') break;
                }
                $connexio = "";
                if (!empty($connector)) {
                    foreach ($G as $key => $value) {
                        if ($key == 'elements') {
                            foreach ($value as $element) {
                                $connexio .= "Grup $element $connector ";
                            }
                            break;
                        }
                    }
                    $connexio = substr($connexio, 0, -1*(strlen($connector)+2));
                    $form->addElement("<p>$connexio &nbsp;");
                    $this->_creaBoto($form, "elimina_connexio_grup_$K", "elimina Connexió", ['id'=>"btn__elimina_connexio_grup_$K"]);
                    $form->addElement("</p>");
                }
            }
            $form->addElement("</div>");
        }
    }

    //S'ha pulsat algun botó [nova Condició]
    private function _tractamentBotoNovaCondicio(&$grups) {
        $k = key($this->params['do']);
        $pat = "/nova_condicio_grup_(.*)/";
        if (preg_match($pat, $k, $g)) {
            $grups["grup_${g[1]}"]['elements'][] = "";
        }
    }

    // Reconstrueix els elements HTML a partir de l'arbre
    private function _recreaArbre(&$form, &$ret, $grups) {
        foreach ($grups as $G => $grup) {
            $g = explode("_", $G)[1];
            if (is_numeric($g)) {
                $values = ['connector_grup' => IocCommon::nz($this->params["connector_grup_$g"]),
                           'projecttype_grup' => IocCommon::nz($this->params["projecttype_grup_$g"])];
                $this->_creaGrup($form, $ret, $g, $values);
                foreach ($grup as $key => $value) {
                    if ($key == "elements") {
                        foreach ($value as $k => $element) {
                            $this->_creaCondicio($form, $ret, $k, $this->params["condicio_${k}_grup_$g"], $g);
                            $form->addElement("<p></p>");
                        }
                    }
                }
                $form->addElement("</div>");
            }
            elseif ($g=="G") {
                $g .= "_".explode("_", $G)[2];
                $values = ['connector_grup' => IocCommon::nz($this->params["connector_grup_$g"])];
                $this->_creaGGrup($form, $ret, $g, $values);
                foreach ($grup as $key => $value) {
                    if ($key == "elements") {
                        foreach ($value as $k => $element) {
                            $this->_creaGCondicio($form, $ret, $grups, $k, $this->params["condicio_${k}_grup_$g"], $g);
                            $form->addElement("<p></p>");
                        }
                    }
                }
                $form->addElement("</div>");
            }
        }
    }

    private function _creaGCondicio(&$form, &$ret, $grups, $n="0", $valor="", $grup="0") {
        $valor = IocCommon::nz($valor, "");
        $aListGrups = array_keys($grups);
        $attrs = ['_text' => "grup ${n}:&nbsp;",
                  'name' => "condicio_${n}_grup_${grup}"];
        $attrs['_options'][] = ['', '- Selecciona un grup -'];
        foreach ($aListGrups as $v) {
            if (strpos($v, "grup")===0) {
                $id = trim($v,"grup_");
                $selected = ($id==$valor) ? "select" : "";
                $attrs['_options'][] = [$id, $v, $selected, false]; //'value','text','select','disabled'
            }
        }
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_listboxfield($attrs));
        $form->addElement("</span>");
        $ret["grup_$grup"]['elements'][] = $valor;
    }

    private function _creaCondicio(&$form, &$ret, $n="0", $value="", $grup="0") {
        $value = IocCommon::nz($value, "");
        $attrs = ['_text' => "condició ${n}:&nbsp;",
                  'name' => "condicio_${n}_grup_${grup}",
                  'type' => "text",
                  'size' => "35",
                  'value' => $value];
        $this->_creaElement($form, $ret, $value, $attrs, $grup);
    }

    private function _creaGGrup(&$form, &$ret, $grup="G_0", $values=[]) {
        $form->addElement(self::DIVGRUP);
        $form->addElement(self::DIVGRUPNAME."<b>Agrupació $grup</b></div>");
        $form->addElement(self::DIVGRUPBOTO);
        $this->_creaBotoNovaCondicio($form, $grup);
        $form->addElement('</div>');
        $form->addElement(self::DIVGRUPCONN);
        $this->_creaConnectorGrup($form, $ret, $values['connector_grup'], $grup);
        $form->addElement('</div>');
    }

    private function _creaGrup(&$form, &$ret, $grup="0", $values=[]) {
        $form->addElement(self::DIVGRUP);
        $form->addElement(self::DIVGRUPNAME."<b>Grup $grup</b></div>");
        $form->addElement(self::DIVGRUPBOTO);
        $this->_creaBotoNovaCondicio($form, $grup);
        $form->addElement('</div>');
        $form->addElement(self::DIVGRUPCONN);
        $this->_creaConnectorGrup($form, $ret, $values['connector_grup'], $grup);
        $this->_creaLlistaTipusDeProjecte($form, $ret, $values['projecttype_grup'], $grup);
        $form->addElement('</div>');
    }

    private function _creaElement(&$form, &$ret, $value, $attrs, $grup="0") {
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");
        $ret["grup_$grup"]['elements'][] = $value;
    }

    private function _creaBotoNovaCondicio(&$form, $grup="0") {
        $this->_creaBoto($form, "nova_condicio_grup_$grup", "nova Condició", ['id'=>"btn__nova_condicio_grup_$grup"]);
    }

    private function _creaConnectorGrups(&$form) {
        $valor = IocCommon::nz($valor, "");
        $values = ['' => "- Selecciona un connector -",
                   'and' => "I",
                   'or' => "O"];
        $connector = form_makeMenuField("connector_de_grups", $values, "", "", "connector de grups:", "idconnector_de_grups");
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_menufield($connector));
        $form->addElement("</span>");
    }

    private function _creaBotoNovaAgrupacio(&$form) {
        $this->_creaBoto($form, "nova_agrupacio", "nova Agrupació", ['id'=>"btn__nova_agrupacio"]);
    }

    private function _creaBotoNouGrup(&$form) {
        $this->_creaBoto($form, "nou_grup", "nou Grup", ['id'=>"btn__nou_grup"]);
    }

    private function _creaConnectorGrup(&$form, &$ret, $valor="", $grup="0") {
        $valor = IocCommon::nz($valor, "");
        $values = ['' => "- Selecciona un connector -",
                   'and' => "I",
                   'or' => "O"];
        $connector = form_makeMenuField("connector_grup_$grup", $values, $valor, "", "connector:", "idconnector_$grup");
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_menufield($connector));
        $form->addElement("</span>");
        $ret["grup_$grup"]["connector"] = $valor;
    }

    private function _creaFiltre(&$form, &$ret) {
        $form->addElement(self::DIVGRUP);
        $attrs = ['_text' => "Filtre pels tipus de projecte:&nbsp;",
                  'name' => "filtre",
                  'type' => "text",
                  'size' => "18",
                  'value' => $this->params['filtre']];
        $this->_creaElement($form, $ret['grups'], IocCommon::nz($this->params["filtre"], ""), $attrs, "F");
        $this->_creaBoto($form, "filtre", "filtre", ['id'=>'btn__filtre', 'tabindex'=>'1']);
        $form->addElement("</div>");
    }

    private function _creaLlistaTipusDeProjecte(&$form, &$ret, $valor="", $grup="0") {
        $valor = IocCommon::nz($valor, "");
        $aListProjectTypes = $this->getListPtypes($this->params['filtre']);
        $attrs = ['_text' => "Tipus de projecte:&nbsp;",
                  'name' => "projecttype_grup_$grup"];
        $attrs['_options'][] = ['', "- Selecciona un tipus de projecte -"];
        foreach ($aListProjectTypes as $v) {
            $selected = ($v['id']==$valor) ? "select" : "";
            $attrs['_options'][] = [$v['id'], $v['name'], $selected, false]; //'value','text','select','disabled'
        }
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_listboxfield($attrs));
        $form->addElement("</span>");
        $ret["grup_$grup"]['projecttype'] = IocCommon::nz($this->params["projecttype_grup_$grup"]);
    }

    private function _creaCheckBox(&$form, &$ret, $valor="", $grup="0") {
        $checkbox = form_makeCheckboxField("checkbox_grup_$grup", $valor, "marca la casella per connectar amb altres grups");
        $form->addElement(form_checkboxfield($checkbox));
        $ret["grup_$grup"]["checkbox"] = IocCommon::nz($valor);
    }

    private function _creaBoto(&$form, $action, $title='', $attrs=array(), $type='submit') {
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
