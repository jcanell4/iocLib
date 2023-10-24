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
            global $auth;
            $this->_auth = &$auth;
            $llista_usuaris = json_decode($this->params['llista_usuaris'], true);
            foreach ($llista_usuaris as $u) {
                //$user, $pass, $mail, $name, $moodle
                $usuari = [$u[0], auth_pwgen($u[0]), $u[1], $u[2], '1'];
                $this->_addUser($usuari);
            }
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

    /**
     * Add an user to auth backend
     * @return bool whether succesful
     */
    private function _addUser($usuari){
        if (!checkSecurityToken()) return false;
        if (!$this->_auth->canDo('addUser')) return false;

        list($user, $pass, $mail, $name, $moodle, $editor, $grps) = $usuari;
        if (empty($user)) return false;

        if ($this->_auth->canDo('modPass')){
            if (empty($pass)){
                $pass = auth_pwgen($user);
            }
        } else {
            if (!empty($pass)){
                msg($this->lang['add_fail'], -1);
                msg($this->lang['addUser_error_modPass_disabled'], -1);
                return false;
            }
        }

        if ($this->_auth->canDo('modName')){
            if (empty($name)){
                msg($this->lang['add_fail'], -1);
                msg($this->lang['addUser_error_name_missing'], -1);
                return false;
            }
        } else {
            if (!empty($name)){
                msg($this->lang['add_fail'], -1);
                msg($this->lang['addUser_error_modName_disabled'], -1);
                return false;
            }
        }

        if ($this->_auth->canDo('modMail')){
            if (empty($mail)){
                msg($this->lang['add_fail'], -1);
                msg($this->lang['addUser_error_mail_missing'], -1);
                return false;
            }
        } else {
            if (!empty($mail)){
                msg($this->lang['add_fail'], -1);
                msg($this->lang['addUser_error_modMail_disabled'], -1);
                return false;
            }
        }
        
        if (empty($moodle)){
            $moodle = '0';
        }

        if (empty($editor)){
            $editor = 'ACE';
        }
        if (($ok = $this->_auth->triggerUserMod('create', array($user,$pass,$name,$mail,$moodle,$editor,$grps)))) {
            msg($this->lang['add_ok'], 1);
            $this->_notifyUser($user, $pass, $moodle);
        } else {
            msg($this->lang['add_fail'], -1);
            msg($this->lang['addUser_error_create_event_failed'], -1);
        }

        return $ok;
    }

    private function _notifyUser($user, $password, $moodle, $status_alert=true) {
        $password = ($moodle===0) ? $password : "Utilitza la contrasenya de moodle";
        if (($sent = auth_sendPassword($user, $password))) {
            if ($status_alert) {
                msg($this->lang['notify_ok'], 1);
            }
        }else if ($status_alert) {
            msg($this->lang['notify_fail'], -1);
        }
        return $sent;
    }

}
