<?php
/**
 * Class SelectProjectsAction: Crea una pàgina amb un formulari per seleccionar els projectes
 *                             d'un tipus i una propietat específics.
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class SelectProjectsAction extends AdminAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        $this->response = ['id' => "select_projects",
                           'title' => "Selecció de projectes",
                           'content' => $this->setFormProjectTypes(),
                           'type' => "html"
                          ];
        return $this->response;
    }

    protected function startProcess() {}

    /**
     * Construeix un formulari amb un element select que conté la llista de tipus de projecte
     */
    private function setFormProjectTypes() {
        include_once(DOKU_INC.'/inc/form.php');
        $ret = [];
        $ret['formId'] = $formId = 'dw__select_projects';
        $ret['list'] = '<h1 class="sectionedit1" id="select_projects">Selecció de projectes</h1>'
                      .'<div class="level1"><p>Selecciona el tipus de projecte i un atribut.</p></div>'
                      ."<div style='text-align:center; padding:10px; width:30%; border:1px solid gray'>";

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'POST'));
        $form->addHidden('sectok', null);
        $form->addHidden('id', "");

        $aListProjectTypes = $this->getListPtypes("pt.*");

        $attrs = ['_text' => "Llista de tipus de projecte:&nbsp;",
                  'name' => "selected_project"];
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
        $button = form_makeButton('submit', 'select_projects', WikiIocLangManager::getLang('btn_apply'), ['form' => $formId]);
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
