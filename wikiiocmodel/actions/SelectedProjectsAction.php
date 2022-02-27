<?php
/**
 * Class SelectedProjectsAction: Crea una pàgina amb la llista de projectes que compleixen
 *                               les condicions selccionades.
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class SelectedProjectsAction extends AdminAction {

    const OBRE_LI = '<li style="list-style-type:none; margin-left:20px;">';

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        $model = $this->getModel();
        $parser = $this->parser($this->params['grups']);
        $listProjects = $model->selectProjectsByType($parser['listProjectTypes']);

        foreach ($listProjects as $project) {
            $data_main = $model->getDataProject($project['id'], $project['projectType'], "main");
            $data_all = $model->getAllDataProject($project['id'], $project['projectType']);
            $root = NodeFactory::getNode($parser['grups'], $parser['mainGroup'], $data_main, $data_all);
            if ($root->getValue()) {
                $workflow = $model->isProjectTypeWorkflow($project['projectType']);
                $llista[] = ['id' => $project['id'],
                             'workflow' => $workflow];
            }
        }

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ID],
                           PageKeys::KEY_TITLE => "Llista de projectes seleccionats i filtrats",
                           PageKeys::KEY_CONTENT => $this->setSelectedProjectsList($llista)['html'],
                           PageKeys::KEY_TYPE => "html_response_form"
                          ];
        return $this->response;
    }

    private function parser($G) {
        $listProjectTypes = [];
        $grups = (is_string($G)) ? json_decode(str_replace("'", '"', $G), true) : $G;
        $mainGroup = "grup_${grups['main_group']}";
        foreach ($grups as $key => $grup) {
            if (preg_match("/grup_(.*)/", $key, $g)) {
                if ($grup['projecttype']) {
                    $listProjectTypes[] = $grup['projecttype'];
                }
            }else {
                unset($grups[$key]);
            }
        }
        return ['mainGroup'=>$mainGroup, 'grups'=>$grups, 'listProjectTypes'=>$listProjectTypes];
    }

    /** Construeix un formulari a partir d'una llista d'elements */
    private function setSelectedProjectsList($llista="") {
        $ret = [];
        $ret['formId'] = $formId = "dw__{$this->params[AjaxKeys::KEY_ID]}";
        $ret['html'] = '<h1 class="sectionedit1" id=dw__"'.$this->params[AjaxKeys::KEY_ID].'">Llista de projectes seleccionats</h1>'
                      .'<div class="level1"><p>Llista de projectes seleccionats amb condicions específiques</p></div>'
                      .'<div style="padding:10px; width:50%;">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);

        foreach ($llista as $elem) {
            $id = $elem['id'];
            $workflow = ($elem['workflow']) ? "workflow&action=view" : "view";
            $form->addElement(self::OBRE_LI);
            $this->_creaCheckBox($form, $id, $checked);
            $form->addElement("<a href='lib/exe/ioc_ajax.php?call=project&do=$workflow&id=$id' data-call='project'>$id</a>");
            $form->addElement("</li>");
        }
        $form->addElement("<div><p>&nbsp;</p>");
        $form->addElement("</div>");

        $ret['html'] .= $form->getForm();
        $ret['html'] .= "</div> ";
        return $ret;
    }

    private function _creaCheckBox(&$form, $id, $checked=FALSE) {
        $attr['alt'] = $id;
        if ($checked)
            $attr['checked'] = TRUE;
        $id = str_replace(":", "__", $id);
        $checkbox = form_makeCheckboxField("checkbox_$id", "0", "", $id, "", $attr);
        $form->addElement(form_checkboxfield($checkbox));
    }

}
