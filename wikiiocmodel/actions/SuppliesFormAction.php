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

    private $datacall = "select_projects";

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        if (isset($this->params['do']['cerca'])) {
            $command = "select_projects";
            $title = "Llistat de projectes seleccionats";
            $type = "html_response_form";
        }else {
            $command = $this->params[AjaxKeys::KEY_ID];
            $title = "Selecció de projectes";
            $type = "html_supplies_form";
        }
        $content = $this->creaForm();

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ID],
                           PageKeys::KEY_TITLE => $title,
                           PageKeys::KEY_CONTENT => $content,
                           AjaxKeys::KEY_ACTION_COMMAND => $command,
                           PageKeys::KEY_TYPE => $type
                          ];
        return $this->response;
    }

    /** Construeix un formulari a partir d'un arbre d'elements rebut del client */
    protected function creaForm() {
        $ret = [];
        $ret['formId'] = $formId = "dw__{$this->params[AjaxKeys::KEY_ID]}";
        $ret['list'] = '<h1 class="sectionedit1" id="id_'.$this->params[AjaxKeys::KEY_ID].'">Selecció de projectes</h1>'
                      //.'<div class="level1"><p>Selecciona les condicions per fer la cerca als projectes.</p></div>'
                      .'<div style="text-align:center; padding:10px; width:70%; border:1px solid gray; border-radius:10px;">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);
//        $this->_creaFiltre($form, $ret['grups']);

        //GRUPS
        $this->_creacioGestioDeGrups($form);
        $main_group = "0";
        $lastGgroup = "0";
        $last_group = "0";

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

            $this->_tractamentParams($grups);
            $this->_tractamentMainGroup($grups, $main_group);
            $this->_tractamentBotoNouGrup($grups, $last_group);
            $this->_tractamentBotoNovaAgrupacio($grups, $lastGgroup);
            $this->_tractamentBotoNovaCondicio($grups);
            $this->_tractamentBotoEliminaCondicio($grups);

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
        $this->_creaBoto($form, "cerca", WikiIocLangManager::getLang('btn_search'), ['id'=> "btn_cerca", 'action'=> $this->datacall]);

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
        $form->addElement("<span>&nbsp;</span>");
        $this->_creaBotoNovaAgrupacio($form);
        $form->addElement("</p>");
    }

    //Recull els nous paràmetres arribats des del client i els introdueix a la matriu de grups
    private function _tractamentParams(&$grups) {
        foreach ($grups as $GR => $grup) {
            if (preg_match("/grup_(.*)/", $GR, $g)) {
                foreach ($grup as $key => $elements) {
                    if ($key == "connector") {
                        if (!empty($this->params["connector_grup_${g[1]}"])) {
                            $grups[$GR]['connector'] = $this->params["connector_grup_${g[1]}"];
                        }
                    }elseif ($key == "projecttype") {
                        if (!empty($this->params["projecttype_grup_${g[1]}"])) {
                            $grups[$GR]['projecttype'] = $this->params["projecttype_grup_${g[1]}"];
                        }
                    }elseif ($key == "elements") {
                        foreach ($elements as $n => $e) {
                            if (!empty($this->params["condicio_${n}_grup_${g[1]}"])) {
                                $grups[$GR]['elements'][$n] = $this->params["condicio_${n}_grup_${g[1]}"];
                            }
                        }
                    }
                }
            }
        }
    }

    private function _tractamentMainGroup($grups, &$main_group) {
        foreach ($grups as $G => $grup) {
            if (preg_match("/grup_(G.*)/", $G, $g)) {
                foreach ($grup['elements'] as $e) {
                    if ($e == "grup_$main_group") {
                        $main_group = $g[1];
                        break 2;
                    }
                }
            }
        }
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

    //S'ha pulsat algun botó [nova Condició]
    private function _tractamentBotoNovaCondicio(&$grups) {
        $k = key($this->params['do']);
        $pat = "/nova_condicio_grup_(.*)/";
        if (preg_match($pat, $k, $g)) {
            $grups["grup_${g[1]}"]['elements'][] = "";
        }
    }

    //S'ha pulsat algun botó [nova Condició]
    private function _tractamentBotoEliminaCondicio(&$grups) {
        $k = key($this->params['do']);
        $pat = "/elimina_condicio_([0-9]+)_grup_(G_)?(.*)/";
        if (preg_match($pat, $k, $g)) {
            unset($grups["grup_${g[2]}${g[3]}"]['elements']["${g[1]}"]);
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
            if (strpos($v, "grup_") === 0 && $v != "grup_$grup" ) {
                $selected = ($v==$valor) ? "select" : "";
                $attrs['_options'][] = [$v, $v, $selected, false]; //'value','text','select','disabled'
            }
        }
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_listboxfield($attrs));
        $form->addElement("<span>&nbsp;&nbsp;</span>");
        $this->_creaBotoEliminaCondicio($form, $n, $grup, "Grup");
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
        $this->_creaBotoEliminaCondicio($form, $n, $grup, "Condició");
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

    private function _creaBotoEliminaCondicio(&$form, $n="0", $grup="0", $text="Condició") {
        $this->_creaBoto($form, "elimina_condicio_${n}_grup_${grup}", "elimina $text", ['id'=>"btn__nova_condicio_${n}_grup_${grup}"]);
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

}
