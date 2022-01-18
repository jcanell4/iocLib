<?php
if (!defined('DOKU_INC')) die();
require_once(DOKU_INC . 'inc/JSON.php');

/**
 * Interface JsonGenerator
 * Les classes que implementen aquesta interficie retornen el valor passat com argument en format JSON fent servir
 * la classe JSON de DokuWiki.
 *
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
interface JsonGenerator {
    const HTML_TYPE              = "html";              //0;
    const TITLE_TYPE             = "title";             //1;
    const INFO_TYPE              = "info";              //2;
    const COMMAND_TYPE           = "command";           //3;
    const ERROR_TYPE             = "error";             //4;
    const LOGIN_INFO             = "login";             //5;
    const SECTOK_DATA            = "sectok";            //6;
    const DATA_TYPE              = "data";              //7;
    const META_INFO              = "metainfo";          //8;
    const REMOVE_CONTENT_TAB     = "remove";            //9;
    const REMOVE_ALL_CONTENT_TAB = "removeall";         //10;
    const CODE_TYPE_RESPONSE     = "code";              //11;
    const SIMPLE_TYPE_RESPONSE   = "simple_data";       //12;
    const ARRAY_TYPE_RESPONSE    = "array";             //13;
    const OBJECT_TYPE_RESPONSE   = "object";            //14;
    const ALERT_TYPE             = "alert";             //15;
    const ADMIN_TAB              = "admin_tab";         //16;
    const MEDIA_TYPE             = "media";             //17;
    const ADMIN_TASK             = "admin_task";        //18;
    const JSINFO                 = "jsinfo";            //19;
    const META_MEDIA_INFO        = "metaMedia";         //20;
    const REVISIONS_TYPE         = "revisions";         //21;
    const EXTRA_CONTENT_STATE    = "extraContentState"; //22;
    const MEDIADETAILS_TYPE      = "mediadetails";      //23;
    const EXTRA_META_INFO        = "extra_metainfo";    //24;
    const DIFF_TYPE              = "diff";              //25;
    const META_DIFF              = "diff_metainfo";     //26;
    const META_MEDIADETAILS_INFO = "metamediadetails";  //27;
    const DRAFT_DIALOG           = "draft_dialog";      //28;
    const HTML_PARTIAL_TYPE      = "html_partial";      //29;
    const EDIT_PARTIAL_TYPE      = "edit_partial";      //30;
    const LOCK_DATA              = "lock_data";         //31;
    const TREE                   = "tree";              //32;
    const NOTIFICATION           = "notification";      //33;
    const CUSTOM_DIALOG          = "custom_dialog";     //34;
    const REQUIRING              = "requiring";         //35;
    const CONTROLMANAGER         = "controlManager";    //36;
    const FORM_TYPE              = "form";              //37;
    const TAB                    = "tab";               //38;
    const RECENTS                = "recents";           //39;
    const TO_PRINT               = "print";             //40;
    const CT_TIMER               = "contentTool_timer"; //41;
    const USER_STATE             = "user_state";        //42;
    const UPDATE_LOCAL_DRAFTS    = "update_local_drafts"; //43;
    const USER_PROFILE           = "user_profile";      //44;
    const PROJECT_EDIT_TYPE      = "project_edit";      //45;
    const PROJECT_VIEW_TYPE      = "project_view";      //46;
    const PROJECT_DIFF_TYPE      = "project_diff";      //47;
    const PROJECT_REQUIRE_TYPE   = "project_require";   //48;
    const RECALL                 = "recall";            //49;
    const META_ERROR_TYPE        = "meta_error_type";   //50;
    const PROJECT_PARTIAL_TYPE   = "project_partial";
    const HTML_FORM_TYPE         = "html_form";
    const HTML_RESPONSE_FORM_TYPE= "html_response_form";
    const HTML_SUPPLIES_FORM_TYPE= "html_supplies_form";

    // Aquestes constants es fan servir com a subtipus
    const ADD_ADMIN_TAB     = "add_admin_tab";
    const REMOVE_ADMIN_TAB  = "remove_admin_tab";
    const PROCESS_FUNCTION  = "process_function";
    const ADD_TAB           = "add_tab";
    const REMOVE_TAB        = "remove_tab";

    const PROCESS_DOM_FROM_FUNCTION = "process_dom_from_function"; //domId afectat + AMD (true/flase) + nom funcio/modul on es troba la funció + extra prams
    const CHANGE_DOM_STYLE = "change_dom_style"; //domId afectat + propietat de l'estil a modificar + valor
    const CHANGE_WIDGET_PROPERTY = "change_widget_property"; //widgetId afectat + propietat a modificar + valor
    const RELOAD_WIDGET_CONTENT = "reload_widget_content"; //widgetId afectat
    const ADD_WIDGET_CHILD = "add_widget_child"; //widgetId afectat + widgetId del fill a afegir + tipus de widget a crear + JSON amb els paràmetres per defecte
    const REMOVE_WIDGET_CHILD = "remove_widget_child"; //widgetId afectat + widgetId del fill a eliminar
    const REMOVE_ALL_WIDGET_CHILDREN = "remove_all_widget_children"; //widgetId afectat

    /**
     * @return mixed hash amb el tipus i les dades a codificar.
     */
    public function getJson();

    /**
     * Retorna les dades codificades en format JSON
     * @return string dades en format JSON
     */
    public function getJsonEncoded();
}

/**
 * Class JSonGeneratorImpl
 * Implementació del JSonGenerator per un sol element.
 *
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
class JSonGeneratorImpl implements JsonGenerator {
    private $value;
    private $type;

    /**
     * Constructor del generador que admet el tipus que ha de ser un dels valors constants declarats a la interface, i
     * el valor son les dades que volem codificar.
     *
     * @param int $type tipus de valor
     * @param mixed $valueToSend valor per condificar
     */
    public function __construct($type, $valueToSend = NULL) {
        $this->type = $type;
        if ($valueToSend != NULL) {
            $this->value = $valueToSend;
        }
    }

    /**
     * @return mixed hash amb el tipus i les dades a codificar.
     */
    public function getJson() {
        $data = array(
            "type" => $this->type,
            "value" => $this->value,
        );
        return $data;
    }

    /**
     * Retorna les dades codificades en format JSON
     * @return string dades en format JSON
     */
    public function getJsonEncoded() {
        $dataToEncode = $this->getJson();
        return json_encode($dataToEncode);
    }
}

/**
 * Class ArrayJSonGenerator
 * Implementació de JsonGenerator per codificar un array d'elements JsonGenerator
 */
class ArrayJSonGenerator implements JsonGenerator {
    private $items;

    /**
     * El constructor d'aquesta classe no accepta paràmetres,
     * els valors a codificar s'afegeixen cridant al mètode add().
     */
    public function __construct() {}

    /**
     * @return JsonGenerator array d'elements afegits per codificar
     */
    public function getJson() {
        return $this->items;
    }

    /**
     * @return string dades codificades en format JSON
     */
    public function getJsonEncoded() {
        $ret = json_encode($this->getJson());
        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            $ret = "ERROR en JsonGenerator->getJsonEncoded(): $error:" . json_last_error_msg();
        }
        return $ret;
    }

    /**
     * @param JSonGenerator $jSonGenerator
     */
    public function add($jSonGenerator) {
        $this->items[] = $jSonGenerator->getJson();
    }
}

/**
 * Class JSonJustEncoded
 * Implementació del JSonGenerator per un sol element que ja és un string
 */
class JSonJustEncoded implements JsonGenerator {

    private $value;

    /**
     * Constructor del generador que admet un valor que son les dades que volem codificar.
     *
     * @param mixed $valueToSend valor a codificar
     */
    public function __construct( $valueToSend = NULL ) {
        if ( $valueToSend != NULL ) {
            $this->value = $valueToSend;
        }
    }

    /**
     * @return string amb les dades originals.
     */
    public function getJson() {
        return $this->value;
    }

    /**
     * Retorna les dades codificades en format JSON
     *
     * @return string dades en format JSON
     */
    public function getJsonEncoded() {
        return $this->getJson();
    }
}
