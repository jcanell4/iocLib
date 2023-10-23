<?php
/**
 * NewUserTeachersAction
 * @culpable rafael
 */
if (!defined("DOKU_INC")) die();
include_once(DOKU_INC.'/inc/form.php');

class NewUserTeachersAction extends AdminAction {

    const DIVGRUP = '<div style="text-align:left; margin:7px; padding:10px; border:1px solid blue; border-radius:8px;">';
    const DIVGRUPNAME = '<div style="float:left;margin:0 0 10px 0;">';
    const DIVGRUPBOTO = '<div style="float:right;text-align:right;margin:0 0 10px 0;">';
    const DIVGRUPCONN = '<div style="clear:left;text-align:left;margin:0 0 10px 0;">connector:&nbsp;';
    const OBRE_SPAN = '<span style="margin:0 20px 10px 0;">';

    private $datacall = "call=new_user_teachers";

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function responseProcess() {
        $content = $this->_createForm();

        $this->response = [AjaxKeys::KEY_ID => $this->params[AjaxKeys::KEY_ID],
                           PageKeys::KEY_TITLE => "Creació usuaris professors",
                           PageKeys::KEY_CONTENT => $content,
                           PageKeys::KEY_TYPE => "html_new_user_teachers_form"
                          ];
        return $this->response;
    }

    private function _mostraLlistaUsuaris(&$form, &$ret) {
        $usuari = $this->params['usuari'];
        $email = $this->params['email'];
        $nom = $this->params['nom_i_cognoms'];
        $llista_usuaris = json_decode($this->params['llista_usuaris'], true);
        $llista_usuaris[] = [$usuari, $email, $nom];

        $form->addHidden('llista_usuaris', json_encode($llista_usuaris));
        $form->addElement(self::DIVGRUP);
        $form->addElement(self::OBRE_SPAN."<b>Llistat dels nous professors</b></span>");

        $html = "<div id=\"new_user_teachers_action\">"
               ."<div class=\"table\">"
               ."<table class=\"inline\">"
               ."<thead>"
               ."<tr>"
               ."<th>usuari</th>"
               ."<th>email</th>"
               ."<th>nom i cognoms</th>"
               ."</tr>"
               ."</thead>"
               ."<tbody>";
        foreach ($llista_usuaris as $u) {
            $html .= "<tr class=\"user_info\">"
                    ."<td>$u[0]</td>"
                    ."<td>$u[1]</td>"
                    ."<td>$u[2]</td>"
                    ."</tr>";
        }
        $html.= "</tbody>"
               ."</table>"
               ."</div>"
               ."</div>";

        $form->addElement($html);
        $form->addElement("</div>");
        $this->_creaBoto($form, "desa", WikiIocLangManager::getLang('btn_save'), ['id'=> "btn__save"]);
    }

    private function _createForm() {
        $ret = [];
        $ret['formId'] = $formId = "dw__{$this->params[AjaxKeys::KEY_ID]}";
        $ret['list'] = '<h1 class="sectionedit1" id="id_'.$this->params[AjaxKeys::KEY_ID].'">Creació dels usuaris pels nous professors</h1>'
                      .'<div style="text-align:center; padding:10px; width:70%; border:1px solid gray; border-radius:10px;">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);
        $form->addHidden('sectok', getSecurityToken());

        $this->_creacioBlocPrincipal($form, $ret);

        $this->_creaBoto($form, "actualitza", WikiIocLangManager::getLang('btn_update'), ['id'=> "btn__actualitza"]);
        $form->addElement("<p>&nbsp;</p>");

        if (isset($this->params['do']['actualitza'])) {
            $this->_mostraLlistaUsuaris($form, $ret);
        }

        $ret['list'] .= $form->getForm();
        $ret['list'] .= "</div> ";
        return $ret;
    }

    //Creació del bloc inicial
    private function _creacioBlocPrincipal(&$form, &$ret) {
        $form->addElement(self::DIVGRUP);
        $form->addElement(self::OBRE_SPAN."<b>Dades del nou professor</b></span>");
        $this->_creaInput($form, $ret, "usuari");
        $this->_creaInput($form, $ret, "email");
        $this->_creaInput($form, $ret, "nom i cognoms");
        $form->addElement("</div>");
    }

    private function _creaInput(&$form, &$ret, $name) {
        $value = "";
        $attrs = ['_text' => "${name}:&nbsp;",
                  'name' => str_replace(" ","_",$name),
                  'type' => "text",
                  'size' => "30",
                  'value' => $value];
        $this->_creaElement($form, $ret, $value, $attrs);
    }

    private function _creaElement(&$form, &$ret, $value, $attrs) {
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");
        $ret['elements'][] = $value;
    }

    private function _creaBoto(&$form, $action, $title='', $attrs=array(), $type='submit') {
        $button = form_makeButton($type, $action, $title, $attrs);
        $form->addElement(self::OBRE_SPAN);
        $form->addElement(form_button($button));
        $form->addElement("</span>");
    }

}
