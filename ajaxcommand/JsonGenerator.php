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
interface JsonGenerator
{
    const HTML_TYPE = 0;
    const TITLE_TYPE = 1;
    const INFO_TYPE = 2;
    const COMMAND_TYPE = 3;
    const ERROR_TYPE = 4;
    const LOGIN_INFO = 5;
    const SECTOK_DATA = 6;
    const DATA_TYPE = 7;
    const META_INFO = 8;
    const REMOVE_CONTENT_TAB = 9;
    const REMOVE_ALL_CONTENT_TAB = 10;
    const CODE_TYPE_RESPONSE = 11;
    const SIMPLE_TYPE_RESPONSE = 12;
    const ARRAY_TYPE_RESPONSE = 13;
    const OBJECT_TYPE_RESPONSE = 14;
    const ALERT_TYPE = 15;
    const ADMIN_TAB = 16;
    const MEDIA_TYPE = 17;
    const REVISIONS_TYPE = 21;
    const ADMIN_TASK = 18;
    const META_MEDIA_INFO = 20;
    const EXTRA_CONTENT_STATE = 22;
    const MEDIADETAILS_TYPE = 23;
    const EXTRA_META_INFO = 24;
    const DIFF_TYPE = 25;
    const META_DIFF = 26;
    const META_MEDIADETAILS_INFO = 27;
    const DRAFT_DIALOG = 28;
    const HTML_PARTIAL_TYPE = 29;
    const EDIT_PARTIAL_TYPE = 30;
    const LOCK_DATA = 31;
    const TREE = 32;
    const NOTIFICATION = 33;
    const CUSTOM_DIALOG = 34;
    const REQUIRING = 35;
    const CONTROLMANAGER = 36;
    const FORM_TYPE = 37;
    const TAB = 38;
    const RECENTS = 39;
    const TO_PRINT = 40;
    const CT_TIMER = 41;
    const USER_STATE = 42;
    const UPDATE_LOCAL_DRAFTS = 43;

    // Aquestes constants es fan servir com a subtipus
    const ADD_ADMIN_TAB = "add_admin_tab";
    const REMOVE_ADMIN_TAB = "remove_admin_tab";
    const PROCESS_FUNCTION = "process_function";
    const ADD_TAB = "add_tab";
    const REMOVE_TAB = "remove_tab";

    /**
     * @const PROCESS_DOM_FROM_FUNCTION domId afectat + AMD (true/flase) + nom funcio/modul on es troba la funció +
     * extra prams
     */
    const PROCESS_DOM_FROM_FUNCTION = "process_dom_from_function";

    /** @const CHANGE_DOM_STYLE domId afectat + propietat de l'estil a modificar + valor */
    const CHANGE_DOM_STYLE = "change_dom_style";

    /** @const CHANGE_WIDGET_PROPERTY widgetId afectat + propietat a modificar + valor */
    const CHANGE_WIDGET_PROPERTY = "change_widget_property";

    const RELOAD_WIDGET_CONTENT = "reload_widget_content"; //widgetId afectat

    /**
     * @const ADD_WIDGET_CHILD  widgetId afectat + widgetId del fill a afegir + tipus de widget a crear + JSON amb els
     * paràmetres per defecte
     */
    const ADD_WIDGET_CHILD = "add_widget_child";

    /** @const REMOVE_WIDGET_CHILD widgetId afectat + widgetId del fill a eliminar */
    const REMOVE_WIDGET_CHILD = "remove_widget_child";

    /** @const REMOVE_ALL_WIDGET_CHILDREN widgetId afectat */
    const REMOVE_ALL_WIDGET_CHILDREN = "remove_all_widget_children";

//    const REMOVE_META_TAB="remove_meta_tab";
//    const REMOVE_ALL_META_TAB="remove_all_meta_tab";
    /** @const JSINFO infomració per el javascript */
    //const JSINFO = "jsinfo";
    const JSINFO = 19;

    /**
     * @return mixed hash amb el tipus i les dades a codificar.
     */
    public function getJson();

    /**
     * Retorna les dades codificades en format JSON
     *
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
class JSonGeneratorImpl implements JsonGenerator
{
    private $value;
    private $type;
    private $encoder;

    /**
     * Constructor del generador que admet el tipus que ha de ser un dels valors constants declarats a la interface, i
     * el valor son les dades que volem codificar.
     *
     * @param int $type tipus de valor
     * @param mixed $valueToSend valor per condificar
     */
    public function __construct($type, $valueToSend = NULL)
    {
        $this->type = $type;
        if ($valueToSend != NULL) {
            $this->value = $valueToSend;
        }
        $this->encoder = new JSON();
    }

    /**
     * @return mixed hash amb el tipus i les dades a codificar.
     */
    public function getJson()
    {
        //$arrayTypes = JSonGenerator::TYPES;
        $arrayTypes = array(
            "html",
            "title",
            "info",
            "command",
            "error",
            "login",
            "sectok",
            "data",
            "metainfo",
            "remove",
            "removeall",
            "code",
            "simple_data",
            "array",
            "object",
            "alert",
            "admin_tab",
            "media",
            "admin_task",
            "jsinfo",
            "metaMedia",
            "revisions", // meta
            "extraContentState",
            "mediadetails",
            "extra_metainfo",
            "diff",
            "diff_metainfo",
            "metamediadetails",
            "draft_dialog",
            "html_partial",
            "edit_partial",
            "lock_data",
            "tree",
            "notification",
            "custom_dialog",
            "requiring",
            "controlManager",
            "form",
            "tab",
            "recents",
            "print",
            "contentTool_timer",
            "user_state",
            "update_local_drafts"
        );
        $data = array(
            "type" => $arrayTypes[$this->type],
            "value" => $this->value,
        );

        return $data;
    }

    /**
     * Retorna les dades codificades en format JSON
     *
     * @return string dades en format JSON
     */
    public function getJsonEncoded()
    {
        $dataToEncode = $this->getJson();

        return $this->encoder->encode($dataToEncode); //json_encode($dataToEncode);
    }
}

/**
 * Class ArrayJSonGenerator
 * Implementació de JsonGenerator per codificar un array d'elements JsonGenerator
 */
class ArrayJSonGenerator implements JsonGenerator {
    private $items;
    private $encoder;

    /**
     * El constructor d'aquesta classe no accepta paràmetres,
     * els valors a codificar s'afegeixen cridant al mètode add().
     */
    public function __construct() {
        $this->encoder = new JSON();
    }

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
        return $this->encoder->encode($this->getJson());
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
