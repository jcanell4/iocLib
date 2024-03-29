<?php
/**
 * Class SuppliesFormAction: Crea una pàgina de formulari per seleccionar els projectes
 *                           que compleixen unes condicions específiques.
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

    private $datacall = "call=selected_projects";

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        $content = $this->creaForm();

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ID],
                           PageKeys::KEY_TITLE => "Selecció de projectes",
                           PageKeys::KEY_CONTENT => $content,
                           PageKeys::KEY_TYPE => "html_supplies_form"
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

        //GRUPS
        $this->_creacioGestioDeGrups($form);
        $this->_creaSeleccioConsulta($form, $this->params['seleccio_consulta']);
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
            if (!isset($grups['lastGgroup']) || !isset($grups['last_group'])) {
                $g = $this->_getLastGroups($grups);
                $lastGgroup = $g['lastGgroup'];
                $last_group = $g['last_group'];
            }else {
                $lastGgroup = $grups['lastGgroup'];
                $last_group = $grups['last_group'];
            }

            $this->_tractamentParams($grups, $this->params['seleccio_consulta'], isset($this->params['do']['actualitza_consulta']));
            $this->_tractamentMainGroup($grups, $main_group);
            $this->_tractamentBotoNouGrup($grups, $last_group);
            $this->_tractamentBotoNovaAgrupacio($grups, $lastGgroup);
            $this->_tractamentBotoNovaCondicio($grups);
            $this->_tractamentBotoEliminaCondicio($grups);

            //Recontrueix el formulari a partir de l'arbre
            $this->_recreaArbre($form, $ret['grups'], $grups, isset($this->params['do']['actualitza_consulta']));
        }
        
        $ret['grups']['main_group'] = $main_group;
        $ret['grups']['lastGgroup'] = $lastGgroup;
        $ret['grups']['last_group'] = $last_group;
        $form->addHidden('grups', json_encode($ret['grups']));
        $form->addElement("</div>");

        $form->addElement("<p>&nbsp;</p>");
        if (isset($this->params['do']['actualitza'])) {
            //BOTÓ CERCA
            $this->_creaBoto($form, "cerca", WikiIocLangManager::getLang('btn_search'), ['id'=> "btn__cerca", 'data-query'=> $this->datacall]);
        }else {
            //BOTÓ ACTUALITZA
            $this->_creaBoto($form, "actualitza", WikiIocLangManager::getLang('btn_update'), ['id'=> "btn__actualitza"]);
        }

        $ret['list'] .= $form->getForm();
        $ret['list'] .= "</div> ";
        return $ret;
    }

    private function _creaSeleccioConsulta(&$form, $valor="") {
        $form->addElement(self::DIVGRUP);
        $form->addElement(self::OBRE_SPAN."<b>Selecció de consulta predefinida</b></span>");
        $valor = IocCommon::nz($valor, "");
        $values = ['' => "- Selecciona una consulta -"];
        $lista = WikiGlobalConfig::getConf('consultes');
        foreach ($lista as $value) {
            $values[$value['value']] = $value['name'];
        }
        $consulta = form_makeMenuField("seleccio_consulta", $values, $valor, "", "consulta:", "idconsulta");
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_menufield($consulta));
        $form->addElement("</span>");
        $form->addElement(self::OBRE_SPAN);
        $this->_creaBotoConsulta($form);
        $form->addElement("</span>");
        $form->addElement("</div>");
    }

    //Creació del grup de grups inicial
    private function _creacioGrupGInicial(&$form, &$ret, $lastGgroup) {
        $values = ['type' => "aggregation",
                   'connector_grup' => $this->params["connector_grup_G_$lastGgroup"],
                   'elements_grup' => [""]];
        $this->_creaGGrup($form, $ret['grups'], "G_$lastGgroup", $values);
        $this->_creaGCondicio($form, $ret['grups'], $ret['grups'], "0", "", "G_$lastGgroup");
        $form->addElement("</div>");
    }

    //Creació del grup simple inicial
    private function _creacioGrupInicial(&$form, &$ret, $last_group) {
        $values = ['type' => "condition",
                   'connector_grup' => $this->params["connector_grup_0"],
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
    private function _tractamentParams(&$grups, $consulta, $actualitza_consulta) {
        if (!empty($actualitza_consulta && $consulta)) {
            $grups = json_decode($consulta, true);
        }else {
            foreach ($grups as $GR => $grup) {
                if (preg_match("/grup_(.*)/", $GR, $g)) {
                    foreach ($grup as $key => $elements) {
                        if ($key == "connector") {
                            if (!empty($this->params["connector_grup_${g[1]}"])) {
                                $grups[$GR]['connector'] = $this->params["connector_grup_${g[1]}"];
                            }
                        }elseif ($key == "branca") {
                            if (!empty($this->params["branca_grup_${g[1]}"])) {
                                $grups[$GR]['branca'] = $this->params["branca_grup_${g[1]}"];
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
            $grups["grup_$last_group"] = ['type' => "condition",
                                          'connector' => "",
                                          'projecttype' => "",
                                          'elements' => [""]];
        }
    }

    //S'ha pulsat el botó [nova Agrupació]
    private function _tractamentBotoNovaAgrupacio(&$grups, &$lastGgroup) {
        if (isset($this->params['do']["nova_agrupacio"])) {
            $lastGgroup++;
            $grups["grup_G_$lastGgroup"] = ['type' => "aggregation",
                                            'connector' => "",
                                            'elements' => [""]];
        }
    }

    //S'ha pulsat algun botó [nova Condició]
    private function _tractamentBotoNovaCondicio(&$grups) {
        if (isset($this->params['do'])) {
            $k = key($this->params['do']);
            $pat = "/nova_condicio_grup_(.*)/";
            if (preg_match($pat, $k, $g)) {
                $grups["grup_${g[1]}"]['elements'][] = "";
            }
        }
    }

    //S'ha pulsat algun botó [nova Condició]
    private function _tractamentBotoEliminaCondicio(&$grups) {
        if (isset($this->params['do'])) {
            $k = key($this->params['do']);
            $pat = "/elimina_condicio_([0-9]+)_grup_(G_)?(.*)/";
            if (preg_match($pat, $k, $g)) {
                unset($grups["grup_${g[2]}${g[3]}"]['elements']["${g[1]}"]);
            }
        }
    }

    // Reconstrueix els elements HTML a partir de l'arbre
    private function _recreaArbre(&$form, &$ret, $grups, $consulta) {
        foreach ($grups as $G => $grup) {
            $g = explode("_", $G)[1];
            if (is_numeric($g)) {
                $connector = ($consulta) ? $grups["grup_$g"]["connector"] : $this->params["connector_grup_$g"];
                $branca = ($consulta) ? $grups["grup_$g"]["branca"] : $this->params["branca_grup_$g"];
                $projecttype = ($consulta) ? $grups["grup_$g"]["projecttype"] : $this->params["projecttype_grup_$g"];
                $values = ['type' => "condition",
                           'connector_grup' => IocCommon::nz($connector),
                           'branca_grup' => IocCommon::nz($branca),
                           'projecttype_grup' => IocCommon::nz($projecttype)];
                $this->_creaGrup($form, $ret, $g, $values);
                foreach ($grup as $key => $value) {
                    if ($key == "elements") {
                        foreach ($value as $k => $element) {
                            $valor = ($consulta) ? $grups["grup_$g"]["elements"][$k] : $this->params["condicio_${k}_grup_$g"];
                            $this->_creaCondicio($form, $ret, $k, IocCommon::nz($valor), $g);
                            $form->addElement("<p></p>");
                        }
                    }
                }
                $form->addElement("</div>");
            }
            elseif ($g=="G") {
                $g .= "_".explode("_", $G)[2];
                $connector = ($consulta) ? $grups["grup_$g"]["connector"] : $this->params["connector_grup_$g"];
                $values = ['type' => "aggregation",
                           'connector_grup' => IocCommon::nz($connector)];
                $this->_creaGGrup($form, $ret, $g, $values);
                foreach ($grup as $key => $value) {
                    if ($key == "elements") {
                        foreach ($value as $k => $element) {
                            $valor = ($consulta) ? $grups["grup_$g"]["elements"][$k] : $this->params["condicio_${k}_grup_$g"];
                            $this->_creaGCondicio($form, $ret, $grups, $k, IocCommon::nz($valor), $g);
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
                  'size' => "70",
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
        $ret["grup_$grup"]["type"] = "aggregation";
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
        $ret["grup_$grup"]["type"] = "condition";
        $this->_creaConnectorGrup($form, $ret, $values['connector_grup'], $grup);
        $this->_creaBrancaGrup($form, $ret, $values['branca_grup'], $grup);
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

    private function _creaBotoConsulta(&$form) {
        $this->_creaBoto($form, "actualitza_consulta", "Actualitza", ['id'=>"btn__actualitza_consulta"]);
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

    private function _creaBrancaGrup(&$form, &$ret, $valor="", $grup="0") {
        $valor = IocCommon::nz($valor, "");
        $attrs = ['_text' => "Branca de l'arbre:&nbsp;",
                  'name' => "branca_grup_$grup",
                  'type' => "text",
                  'size' => "18",
                  'value' => $valor];
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");
        $ret["grup_$grup"]['branca'] = IocCommon::nz($valor);
    }

    private function _creaLlistaTipusDeProjecte(&$form, &$ret, $valor="", $grup="0") {
        $valor = IocCommon::nz($valor, "");
        $aListProjectTypes = $this->getListPtypes();
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
        $ret["grup_$grup"]['projecttype'] = IocCommon::nz($valor);
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

    /**
     * Recupera el valor de last_grup fent un recorregut per l'arbre
     * @param array $grups : array de grups
     * @return array valor de l'última agregació i l'últim grup
     */
    private function _getLastGroups($grups) {
        foreach ($grups as $G => $grup) {
            $g = explode("_", $G)[1];
            if (is_numeric($g)) {
                $ret['last_group'] = $g;
            }
            elseif ($g=="G") {
                $g = explode("_", $G)[2];
                $ret['lastGgroup'] = $g;
            }
        }
        return $ret;
    }

}
