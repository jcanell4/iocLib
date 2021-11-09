<?php
require_once DOKU_INC . 'inc/parserutils.php';
require_once DOKU_INC . 'inc/parser/parser.php';
require_once DOKU_INC . 'inc/parser/handler.php';
require_once DOKU_INC . 'lib/lib_ioc/translators/translatorParserClasses.php';

if (!function_exists('array_key_last')) {
    function array_key_last(array $array) {
        if (!empty($array)) return key(array_slice($array, -1, 1, true));
    }
}


/**
 * @author josep
 */
abstract class AbstractMarkDownTranslator {

    protected $modes = null;
    protected $rootLevelValues = array(1);

    function init($params) {
        // ALERTA[Xavi]! Això és correcte? no hauria de ser $this->rootLevelValues = $params["rootLevelValues"]; ?
//        $this->$rootLevelValues = $params["rootLevelValues"];
    }

    function getInstructions($text) {
        global $PARSER_MODES;
        $aux_parser_modes = $PARSER_MODES;

        $PARSER_MODES = $this->getParserModes();
        $this->initParserModes();

        // Create the parser
        $Parser = new Doku_Parser();

        // Add the Handler
        $Parser->Handler =& $this->createInstanceHandler();

        //add modes to parser
        foreach ($this->modes as $mode) {
            $Parser->addMode($mode['mode'], $mode['obj']);
        }

        // Do the parsing
        trigger_event('TRANSLATOR_WIKITEXT_PREPROCESS', $text);
        $p = $Parser->parse($text);
        //  dbg($p);
        $PARSER_MODES = $aux_parser_modes;

        return $p;
    }

    function getRenderedContent($instructions, &$info) {
        if (is_null($instructions)) return '';

        $Renderer =& $this->createInstanceRender();
        if (is_null($Renderer)) return null;

        $Renderer->reset();

        // Loop through the instructions
        foreach ($instructions as $instruction) {
            // Execute the callback against the Renderer
            if (method_exists($Renderer, $instruction[0])) {
                call_user_func_array(array(&$Renderer, $instruction[0]), $instruction[1] ? $instruction[1] : array());
            }
        }

        //set info array
        $info = $Renderer->info;

        // Post process and return the output
        $data = array($this->getRenderFormat(), & $Renderer->doc);
        trigger_event('RENDERER_CONTENT_POSTPROCESS', $data);
        return $Renderer->doc;
    }

    abstract static function getParserModes();

    abstract function getStdModes();

    abstract function getFmtModes();

    abstract function createInstanceHandler();

    abstract function createInstanceRender();

    abstract function getRenderFormat();

    function initParserModes() {
//        global $conf;

        if ($this->modes != null && !defined('DOKU_UNITTEST')) {
            return;
        }

        // we now collect all syntax modes and their objects, then they will
        // be sorted and added to the parser in correct order
        $this->modes = array();

        // add syntax plugins
        $pluginlist = plugin_list('syntax');
        if (count($pluginlist)) {
            $obj = null;
            foreach ($pluginlist as $p) {
                /** @var DokuWiki_Syntax_Plugin $obj */
                if (!$obj = plugin_load('syntax', $p)) continue; //attempt to load plugin into $obj
                $this->getParserModes()[$obj->getType()][] = "plugin_$p"; //register mode type
                //add to modes
                $this->modes[] = array(
                    'sort' => $obj->getSort(),
                    'mode' => "plugin_$p",
                    'obj' => $obj,
                );
                unset($obj); //remove the reference
            }
        }

        // add default modes
        $std_modes = $this->getStdModes();
        foreach ($std_modes as $m) {
            $class = "Doku_Parser_Mode_$m";
            $obj = new $class();
            $this->modes[] = array(
                'sort' => $obj->getSort(),
                'mode' => $m,
                'obj' => $obj
            );
        }

        // add formatting modes
        $fmt_modes = $this->getFmtModes();
        foreach ($fmt_modes as $m) {
            $obj = new Doku_Parser_Mode_formatting($m);
            $this->modes[] = array(
                'sort' => $obj->getSort(),
                'mode' => $m,
                'obj' => $obj
            );
        }

        //sort modes
        usort($this->modes, 'p_sort_modes');
    }
}

class MarkDown2DikuWikiTranslator extends AbstractMarkDownTranslator {
    static function getParserModes() {
        $ret = array(
            // containers are complex modes that can contain many other modes
            // hr breaks the principle but they shouldn't be used in tables / lists
            // so they are put here
            'container' => array(/*'md2dw_listblock','md2dw_table','md2dw_quote','md2dw_hr'*/),

            // some mode are allowed inside the base mode only
            'baseonly' => array('md2dw_header'),

            // modes for styling text -- footnote behaves similar to styling
            'formatting' => array(/*'md2dw_strong', 'md2dw_emphasis', 'md2dw_underline', 'md2dw_monospace',
                                    'md2dw_subscript', 'md2dw_superscript', 'md2dw_deleted', 'md2dw_footnote'*/),

            // modes where the token is simply replaced - they can not contain any
            // other modes
            'substition' => array(/*'md2dw_internallink','md2dw_media',
                                    'md2dw_externallink','md2dw_linebreak','md2dw_emaillink',
                                    'md2dw_windowssharelink','md2dw_filelink','md2dw_notoc',
                                    'md2dw_nocache','md2dw_multiplyentity','md2dw_quotes','md2dw_rss'*/),

            // modes which have a start and end token but inside which
            // no other modes should be applied
            'protected' => array(/*'md2dw_preformatted','md2dw_code','md2dw_file','md2dw_php','md2dw_html','md2dw_htmlblock','md2dw_phpblock'*/),

            // inside this mode no wiki markup should be applied but lineendings
            // and whitespace isn't preserved
            'disabled' => array(/*'md2dw_unformatted'*/),

            // used to mark paragraph boundaries
            'paragraphs' => array('mddweol')
        );
        return $ret;
    }

    function getStdModes() {
        // add default modes
        $std_modes = array(/*'md2dw_listblock','md2dw_preformatted','md2dw_notoc','md2dw_nocache',*/
            'md2dw_header'/*,'md2dw_table','md2dw_linebreak','md2dw_footnote','md2dw_hr',
                'md2dw_unformatted','md2dw_php','md2dw_html','md2dw_code','md2dw_file','md2dw_quote',
                'md2dw_internallink','md2dw_rss','md2dw_media','md2dw_externallink',
                'md2dw_emaillink','md2dw_windowssharelink'*/, 'mddweol');
        if (WikiGlobalConfig::getConf('typography')) {
            /*$std_modes[] = 'md2dw_quotes';
            $std_modes[] = 'md2dw_multiplyentity';*/
        }
        return $std_modes;
    }

    function getFmtModes() {
        $fmt_modes = array(/*'md2dw_strong','md2dw_emphasis','md2dw_underline','md2dw_monospace',
                            'md2dw_subscript','md2dw_superscript','md2dw_deleted'*/);

        return $fmt_modes;
    }

    function createInstanceHandler() {
        require_once DOKU_INC . 'lib/lib_ioc/translators/markdownHandler.php';
        return new MarkDown2DokuWikiHandler($this->rootLevelValues);
    }

    function createInstanceRender() {
        require_once DOKU_INC . 'lib/lib_ioc/translators/markdownRenderer.php';
        return new MarkDown2DokuWikiRender($this->rootLevelValues);
    }

    function getRenderFormat() {
        return "md2dwtranslator";
    }
}


class DikuWiki2MarkDownTranslator extends AbstractMarkDownTranslator {
    static function getParserModes() {
        $ret = array(
            // containers are complex modes that can contain many other modes
            // hr breaks the principle but they shouldn't be used in tables / lists
            // so they are put here
            'container' => array(/*'dw2md_listblock','dw2md_table','dw2md_quote','dw2md_hr'*/),

            // some mode are allowed inside the base mode only
            'baseonly' => array('dw2md_header'),

            // modes for styling text -- footnote behaves similar to styling
            'formatting' => array(/*'dw2md_strong', 'dw2md_emphasis', 'dw2md_underline', 'dw2md_monospace',
                                    'dw2md_subscript', 'dw2md_superscript', 'dw2md_deleted', 'dw2md_footnote'*/),

            // modes where the token is simply replaced - they can not contain any
            // other modes
            'substition' => array(/*'dw2md_internallink','dw2md_media',
                                    'dw2md_externallink','dw2md_linebreak','dw2md_emaillink',
                                    'dw2md_windowssharelink','dw2md_filelink','dw2md_notoc',
                                    'dw2md_nocache','dw2md_multiplyentity','dw2md_quotes','dw2md_rss'*/),

            // modes which have a start and end token but inside which
            // no other modes should be applied
            'protected' => array(/*'dw2md_preformatted','dw2md_code','dw2md_file','dw2md_php','dw2md_html','dw2md_htmlblock','dw2md_phpblock'*/),

            // inside this mode no wiki markup should be applied but lineendings
            // and whitespace isn't preserved
            'disabled' => array(/*'dw2md_unformatted'*/),

            // used to mark paragraph boundaries
            'paragraphs' => array('mddweol')
        );
        return $ret;
    }

    function getStdModes() {
        // add default modes
        $std_modes = array(/*'dw2md_listblock','dw2md_preformatted','dw2md_notoc','dw2md_nocache',*/
            'dw2md_header'/*,'dw2md_table','dw2md_linebreak','dw2md_footnote','dw2md_hr',
                'dw2md_unformatted','dw2md_php','dw2md_html','dw2md_code','dw2md_file','dw2md_quote',
                'dw2md_internallink','dw2md_rss','dw2md_media','dw2md_externallink',
                'dw2md_emaillink','dw2md_windowssharelink'*/, 'mddweol');
        if (WikiGlobalConfig::getConf('typography')) {
            /*$std_modes[] = 'dw2md_quotes';
            $std_modes[] = 'dw2md_multiplyentity';*/
        }
        return $std_modes;
    }

    function getFmtModes() {
        $fmt_modes = array(/*'dw2md_strong','dw2md_emphasis','dw2md_underline','dw2md_monospace',
                'dw2md_subscript','dw2md_superscript','dw2md_deleted'*/);

        return $fmt_modes;
    }

    function createInstanceHandler() {
        require_once DOKU_INC . 'lib/lib_ioc/translators/markdownHandler.php';
        return new DokuWiki2MarkDownHandler();
    }

    function createInstanceRender() {
        require_once DOKU_INC . 'lib/lib_ioc/translators/markdownRenderer.php';
        return new DokuWiki2MarkDownRender();
    }

    function getRenderFormat() {
        return "dw2mdtranslator";
    }

}

/**
 * Class AbstractTranslator
 * @author Xavier Garcia
 */
abstract class AbstractTranslator {
    public static function translate($text, $params, &$extra) {
        throw new UnimplementedTranslatorException();
    }
}

class Hmtl2DWTranslator extends AbstractTranslator {

    const DEBUG_STRUCTURE = false;

    public static function translate($text, $params, &$extra) {

        // no es pot ficar en el constructor perquè aquesta funció és estàtica

        if (self::DEBUG_STRUCTURE) {
            Logger::init(1, "HTML2DW-Debug.log");

            Logger::debug("### WIOCCLSTRUCTURE START ###\n" . json_encode($params['wioccl_structure']['structure']) . "\n### WIOCCLSTRUCTURE ###\n", 0, __LINE__, basename(__FILE__), 1, false);

            Logger::debug("### HTML SOURCE START ###\n" . $text . "### HTML SOURCE END ###\n", 0, __LINE__, basename(__FILE__), 1, true);
        }

        $header = '';
        if (isset($params[PageKeys::KEY_WIOCCL_STRUCTURE])) {
            $header .= ":###\n";
            foreach ($params[PageKeys::KEY_WIOCCL_STRUCTURE]['header'] as $data) {
                $header .= "~~" . $data ."~~\n";
            }
            $header .= "###:\n";
        }
        // si s'inclou estructura, cal afegir la capçalera

        // TODO: com passem la estructura? -> la fiquem directament al Html2DWParser?
        Html2DWParser::initializeStructure($params[PageKeys::KEY_WIOCCL_STRUCTURE]['structure']);

        $text = $header . $text;

        // Sempre s'afegeixen 2 salts de linia adicionals al final quan es desa
        // perque els documents DW sempre tenen com a mínim un \n al final, i aquest és embolcallat
        // com <p>\n</p>, això provoca el doble salt al final del document.
        // Per solventar-lo fem un trim i afegim un únic salt de línia final.
        $result = trim(Html2DWParser::getValue($text)) . "\n";

        if (self::DEBUG_STRUCTURE) {
            Logger::debug("### DW START ###\n" . $result . "### DW END ###\n", 0, __LINE__, basename(__FILE__), 1, true);
        }
        return $result;
        //return Html2DWParser::getValue($text);
    }
}

class DW2HtmlTranslator extends AbstractTranslator {

    // canviar a true/false fa que es cridi a la funció debugStructure() i s'afegeixi el resultat a cada instrucció
    const DEBUG_STRUCTURE = false;


    public static function translate($text, $params, &$extra, $isPartial = false) {
        global $plugin_controller;

        // no es pot ficar en el constructor perquè aquesta funció és estàtica

        if (self::DEBUG_STRUCTURE) {
            Logger::init(1, "DW2HTML-Debug.log");
            Logger::debug("### DW SOURCE START ###\n" . $text . "### DW SOURCE END ###\n", 0, __LINE__, basename(__FILE__), 1, false);
        }

        $headerData = [];
        if (preg_match_all("/~~(.*?)~~/ms", $text, $matches)) {
            $headerData = $matches[1];
        }

        $text = preg_replace("/:###.*?~~.*?~~\n?###:\n/ms", "", $text, 1, $counter);

        // Hi ha com a mínim un cas en que hi ha USE:WIOCCL sense tancar el readonly: al ptfplogse
        if ($counter===0) {
            $text = preg_replace("/:###.*?~~USE:WIOCCL~~\n/ms", "", $text, 1, $counter);
        }

        if ($counter > 0 || isset($params['generateStructure']) && $params['generateStructure']) {


            $dataSource = $plugin_controller->getCurrentProjectDataSource($params['projectOwner'], $params['projectSourceType']);


            WiocclParser::$generateStructure = true;


            $rootId = 0;
            if (isset($params['rootRef'])) {
                $rootId = $params['rootRef'];
            }

            $counter = 1;

            if (isset($params['nextRef'])) {
                $counter = $params['nextRef'];
            }


            WiocclParser::resetStructure(self::DEBUG_STRUCTURE, $rootId, $counter);


            if ($isPartial && $rootId >0) {
                WiocclParser::setInner(true);
                $null = []; //necesari perquè es passa per referència però no es fa servir
                $text = WiocclParser::getValue($text, [], $dataSource, $null, false);

                WiocclParser::setInner(false);
            } else {
                $text = WiocclParser::getValue($text, [], $dataSource);
            }

            if (self::DEBUG_STRUCTURE) {
                Logger::debug("### DW AFTER WIOCCL PARSE START ###\n" . $text . "### DW AFTER WIOCCL PARSE END ###\n", 0, __LINE__, basename(__FILE__), 1, true);
            }

            WiocclParser::$generateStructure = false;

            if ($extra == NULL) {
                $extra = [];
            }

            $extra['wioccl_structure'] = [
                'header' => $headerData,
                'structure' => WiocclParser::getStructure(),
                'fields' => array_keys($dataSource)
            ];

            // dins es genera un json per comprovar els resultats de la estructura

            if (self::DEBUG_STRUCTURE) {
                Logger::debug("### WIOCCLSTRUCTURE START ###\n" . json_encode($extra['wioccl_structure']['structure']) . "\n### WIOCCLSTRUCTURE ###\n", 0, __LINE__, basename(__FILE__), 1, true);
            }
            $nextKey = max(array_key_last($extra['wioccl_structure']['structure']) + 1, $counter);
            $extra['wioccl_structure']['structure']['next'] = strval($nextKey);

            if (self::DEBUG_STRUCTURE) {
                static::debugStructure(WiocclParser::getStructure(), $headerData);
            }


        } else {
            $dataSource = [];
        }

        if ($isPartial) {
            // TODO: determinar si el $inline ha de ser true o false? <-- les traduccions parcials sempre son inline
            $inline = true;
            $result = DW2HtmlInstruction::parseContent2($text, $inline);
        } else {
            $result = DW2HtmlParser::getValue($text, [], $dataSource);
        }

        if (self::DEBUG_STRUCTURE) {
            Logger::debug("### HTML START ###\n" . $result . "### HTML END ###\n", 0, __LINE__, basename(__FILE__), 1, true);
        }

        return $result;

    }


    protected static function debugStructure($structure, $header) {
        // Primer hem de convertir la estructura en un array associatiu

        reset($structure);
        $root = &$structure[key($structure)];

        // El open del root no és correcte (però és irrellevant) pel document comple
        // són correctes per la edició d'un wioccl concret
//        $root->open = "";
//        $root->close = "";

        $tree = static::getNode($root);
        $json = json_encode($tree);


        // Reconstruim la capçalera
        $meta = ":###\n";
        foreach ($header as $value) {
            $meta .= '~~' . $value . "~~\n";
        }
        $meta .= "###:\n";


        // Provem a reconstruir el document a partir dels nodes
        // TODO: fer servir el nou métode ToWioccl() del WiocclStructureItem (no caldrà cridar al getNode)
        $text = $meta . static::getText($root, $structure);


    }

    protected static function getText($node, $structure) {

        $text = sprintf($node->open, $node->attrs);

        $children = '';

        foreach ($node->children as $childId) {
            $children .= static::getText($structure[$childId], $structure);
        }

        $text .= $children . $node->close;

        return $text;
    }


    private static function getNode($item) {
        // Primer hem de convertir la estructura en un array associatiu
        $node = [
            'parent' => $item->parent !== NULL ? $item->parent : NULL, // ALERTA! desem el id perquè si no es provoca un bucle infinit no, només s'afegeixen complets els fills
            'type' => $item->type,
            'result' => $item->result, // TODO: eliminar, només es fa servir als tests
            'id' => $item->id,
            'open' => $item->open,
            'attrs' => $item->attrs,
            'close' => $item->close,
            'isClone' => $item->isClone,
            'children' => []
        ];

        $children = $item->getChildren();

        for ($i = 0; $i < count($children); $i++) {
            $node['children'][] = static::getNode($children[$i]);
        }

        return $node;
    }
}

