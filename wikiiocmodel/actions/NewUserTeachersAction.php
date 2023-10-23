<?php
/**
 * NewUserTeachersAction
 * @culpable rafael
 */
if (!defined("DOKU_INC")) die();
include_once(DOKU_INC.'/inc/form.php');

class NewUserTeachersAction extends AdminAction {

    const DIVGRUP = '<div style="text-align:left; margin:7px; padding:10px; border:1px solid #E0E0FF; border-radius:6px;">';
    const P_TITLE = '<p style="margin:0 0 10px 10px; font-weight: bold;">';
    const SPAN_LEFT = '<span style="margin-left:10px;">';
    const SPAN_CENTER = '<div style="display:block; margin:10px auto; width:20%;">';

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

    private function _createForm() {
        $ret = [];
        $ret['formId'] = $formId = "dw__{$this->params[AjaxKeys::KEY_ID]}";
        $ret['list'] = '<h1 class="sectionedit1" id="id_'.$this->params[AjaxKeys::KEY_ID].'">Creació dels usuaris pels nous professors</h1>'
                      .'<div style="text-align:center; padding:10px; width:70%; border:1px solid gray; border-radius:10px;">';

        $form = new Doku_Form(array('id' => $formId, 'name' => $formId, 'method' => 'GET'));
        $form->addHidden('id', $this->params[AjaxKeys::KEY_ID]);
        $form->addHidden('sectok', getSecurityToken());

        $this->_creacioBlocPrincipal($form, $ret);

        if (isset($this->params['do']['desa'])) {
            //class admin_plugin_usermanager extends DokuWiki_Admin_Plugin {
            global $auth;
            $this->_auth = $auth;
            $pass = auth_pwgen($user);
            $moodle = '1';
            $editor = 'ACE';
            $this->_notifyUser($user, $pass, $moodle);
        }
        elseif (isset($this->params['do']['actualitza'])) {
            $this->_mostraLlistaUsuaris($form);
        }

        $ret['list'] .= $form->getForm();
        $ret['list'] .= "</div> ";
        return $ret;
    }

    private function _mostraLlistaUsuaris(&$form) {
        $usuari = $this->params['usuari'];
        $email = $this->params['email'];
        $nom = $this->params['nom_i_cognoms'];
        $llista_usuaris = json_decode($this->params['llista_usuaris'], true);
        $llista_usuaris[] = [$usuari, $email, $nom];

        $form->addHidden('llista_usuaris', json_encode($llista_usuaris));
        $form->addElement(self::DIVGRUP);
        $form->addElement(self::P_TITLE."Llistat dels nous professors</p>");

        $html = "<div id=\"new_user_teachers_action\"><div class=\"table\">"
               ."<table class=\"inline\">"
               ."<thead><tr>"
               ."<th>usuari</th><th>email</th><th>nom i cognoms</th>"
               ."</tr></thead>"
               ."<tbody>";
        foreach ($llista_usuaris as $u) {
            $html .= "<tr class=\"user_info\">"
                    ."<td>$u[0]</td><td>$u[1]</td><td>$u[2]</td>"
                    ."</tr>";
        }
        $html.= "</tbody></table></div></div>";

        $form->addElement($html);
        $this->_creaBoto($form, "desa", WikiIocLangManager::getLang('btn_save'), ['id'=> "btn__save"]);
        $form->addElement("</div>");
    }

    //Creació del bloc inicial
    private function _creacioBlocPrincipal(&$form, &$ret) {
        $form->addElement(self::DIVGRUP);
        $form->addElement(self::P_TITLE."Dades del nou professor</p>");
        $this->_creaInput($form, $ret, "usuari", "15");
        $this->_creaInput($form, $ret, "email");
        $this->_creaInput($form, $ret, "nom i cognoms");
        $this->_creaBoto($form, "actualitza", WikiIocLangManager::getLang('btn_update'), ['id'=> "btn__actualitza"]);
        $form->addElement("</div>");
    }

    private function _creaInput(&$form, &$ret, $name, $size="30") {
        $value = "";
        $attrs = ['_text' => "${name}:&nbsp;",
                  'name' => str_replace(" ","_",$name),
                  'type' => "text",
                  'size' => $size,
                  'value' => $value];
        $this->_creaElement($form, $ret, $value, $attrs);
    }

    private function _creaElement(&$form, &$ret, $value, $attrs) {
        $form->addElement(self::SPAN_LEFT);
        $form->addElement(form_field($attrs));
        $form->addElement("</span>");
        $ret['elements'][] = $value;
    }

    private function _creaBoto(&$form, $action, $title='', $attrs=array(), $type='submit') {
        $button = form_makeButton($type, $action, $title, $attrs);
        $form->addElement(self::SPAN_CENTER);
        $form->addElement(form_button($button));
        $form->addElement("</span>");
    }

}
