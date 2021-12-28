<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';


class DW2HtmlParser extends IocParser {


    public static $inline = false;

    public static function isInline() {
        return static::$inline;
    }

    public static function setInline($inline) {
        static::$inline = $inline;
    }

        // Reemplaç special que es porta a terme abans de generar i processar els tokens, per exemple l'escapament de barres
    // es porta a terme cada vegada que es processa un text, així doncs les barres dintre d'una taula es processan dues
    // vegades, la primera quan es processa la taula i la segona quan es processa el contingut e la cel·la.
    // Fent servir el $forceReplacement ens assegurem que sempre es farà el reemplaç sense importar el nombre de
    // processaments portats a terme.

    public static $forceReplacements = [
        "/\\\\\\\\ /ms" => '<br />',
        "/\\\\\\\\\n/ms" => '<br />',
        // Alerta, encara que sembla el mateix un espai es ASCII 32 i l'altre es ASCII 160
        "/^[  ]+\n/ms" => "\n",
        "/\*{3}/ms" => '* **'
    ];

    public static $defaultContainer = ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlParagraph', 'action' => 'open', 'extra' => ['replacement' => ["<p>", "</p>"], 'regex' => TRUE, 'block' => TRUE]];

    protected static $removeTokenPatterns = [
//        '/\n/' // No es poden eliminar els salts perquè son imprescindibles per determinar el final dels contenidors/paràgraphs
    ];

    public static $referenceStack = [];

    protected static function getVideoOrigin() {
        echo implode('|', array_keys(SharedConstants::ONLINE_VIDEO_CONFIG['origins']));

        return implode('|', array_keys(SharedConstants::ONLINE_VIDEO_CONFIG['origins']));
    }


    protected static $tokenPatterns = [
        // Elements de block

        "<newcontent>(.*?)<\/newcontent>" => [
            'state' => 'newcontent',
        ],

        "<quiz .*?>(.*?)<\/quiz>" => [
            'state' => 'quiz',
        ],

        "^----\n" => [
            'state' => 'hr',
        ],


        "={2,6}" => [
            'state' => 'header'
        ],

        "\[{2}(.*?)\]{2}" => [
            'state' => 'link'
        ],

        "{{soundcloud>.*?:.*?}}" => [
            'state' => 'sound'
        ],

        "{{iocgif>(.*?)}}" => [
            'state' => 'gif'
        ],

        "{{(?:" . SharedConstants::ONLINE_VIDEO_PARTIAL_PATTERN . ")>.*?}}" => [
            'state' => 'video'
        ],

        "{{(.*?)}}" => [
            'state' => 'image'
        ],

        "^::.*?:.*?:::\n?$" => [
//        "::.*?:.*?:::" => [
            'state' => 'box'
        ],

        "[\^\|](.*?)*[\|\^]\n" => [
            'state' => 'row'
        ],

        "<code.*?>(.*?)<\/code>\n?" => [
            'state' => 'code',
        ],

        "((^  [^:\-\*].*?\n)+?)(?=^ ?[^ ].*|$)" => [
            'state' => 'code',
        ],


        "<file>(.*?)<\/file>\n?" => [
            'state' => 'code',
        ],

        "(?: {2})+[\*-](.*?)\n" => [
            'state' => 'list-item'
        ],

        "\*\*" => [
            'state' => 'bold'
        ],

        ":\/\/" => [
            'state' => 'ignored'
        ], // protocols


        "\/\/" => [
            'state' => 'italic'
        ],

        "__" => [
            'state' => 'underline'
        ],

        "''(.*?)''" => [
            'state' => 'code', // inline
        ],



        "<note>(.*?)<\/note>" => [
            'state' => 'note'
        ],


        ":table:.*?:" => [
            'state' => 'link-special'
        ],

        ":accounting:.*?:" => [
            'state' => 'link-special'
        ],

        ":figure:.*?:" => [
            'state' => 'link-special'
        ],

        // ALERTA: Aquestes han d'anar sempre el final


        "\[readonly-open\]" => [
            'state' => 'readonly-open'
        ],

        "\[readonly-close\]" => [
            'state' => 'readonly-close'
        ],

        "\[ref=.*?\]" => [
            'state' => 'wioccl-open'
        ],

        "\[\/ref=.*?\]" => [
            'state' => 'wioccl-close'
        ],


        "\n\n+?" => [
            'state' => 'paragraph'
        ],


        "\n" => [
            'state' => 'close'
        ],


    ];

    // ALERTA! La key es un string, no una expresió regular
    protected static $tokenKey = [

        "<newcontent>(.*?)<\/newcontent>" => ['state' => 'newcontent', 'type' => 'newcontent', 'class' => 'DW2HtmlNewContent', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'block' => TRUE]],

        "<quiz (.*?)>(.*?)<\/quiz>" => ['state' => 'quiz', 'type' => 'quiz', 'class' => 'DW2HtmlQuiz', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'block' => TRUE]],


        "{{(?:" . SharedConstants::ONLINE_VIDEO_PARTIAL_PATTERN . ")>(.*?)}}" => ['state' => 'video', 'type' => 'video', 'class' => 'DW2HtmlMedia', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'block' => TRUE]],

        // ALERTA! no ha de ser regex, si es posa com a regex es pot considerar match de les captures multilínia
        "----\n" => ['state' => 'hr', 'type' => 'hr', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'open', 'extra' => ['replacement' => "<hr>\n", 'block' => TRUE, 'regex' => TRUE]],


        "^::(.*?):(.*?):::\n?" => ['state' => 'box', 'type' => 'box', 'class' => 'DW2HtmlBox', 'action' => 'self-contained',
            'extra' => ['regex' => TRUE, 'block' => TRUE]],


        "[\^\|](.*?)*[\|\^]\n" => ['state' => 'row', 'type' => 'row', 'class' => 'DW2HtmlRow', 'action' => 'self-contained',
            'extra' => ['regex' => TRUE, 'block' => TRUE]],


        "={2,6}\n?" => ['state' => 'header', 'type' => 'header', 'class' => 'DW2HtmlHeader', 'action' => 'open-close', 'extra' => ['regex' => TRUE, 'block' => TRUE]],

        '^\[{2}(.*?)\]{2}' => ['state' => 'link', 'type' => 'a', 'class' => 'DW2HtmlLink', 'action' => 'self-contained', 'extra' => ['replacement' => ["<a ", "</a>"], 'regex' => TRUE]],

        ":\/\/" => ['state' => 'ignored', 'type' => 'protocol', 'class' => 'DW2HtmlIgnored', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],


        '{{soundcloud>(.*?):(.*?)}}' => ['state' => 'sound', 'type' => 'sound', 'class' => 'DW2HtmlSound', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'block' => TRUE]],

        "^{{iocgif>(.*?)}}" => ['state' => 'gif', 'type' => 'image', 'class' => 'DW2HtmlImageGIF', 'action' => 'self-contained', 'extra' => ['replacement' => ["<img ", ""], 'regex' => TRUE, 'block' => TRUE]],


        "^{{(.*?)}}" => ['state' => 'image', 'type' => 'image', 'class' => 'DW2HtmlImage', 'action' => 'self-contained', 'extra' => ['replacement' => ["<img ", ""], 'regex' => TRUE, 'block' => TRUE]],


//        ':table:(.*?):' => ['state' => 'box', 'type' => 'table', 'class' => 'DW2HtmlLinkSpecial', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'type' => 'table']],
//        ':figure:(.*?):' => ['state' => 'box', 'type' => 'figure', 'class' => 'DW2HtmlLinkSpecial', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'type' => 'figure']],

        "<code.*?>(.*?)<\/code>\n?" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<pre><code>", "</code></pre>\n"], 'regex' => TRUE, 'block' => TRUE]],
        "((^  [^:\-\*].*?\n)+?)(?=^ ?[^ ].*|$)" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<pre><code>", "</code></pre>\n"], 'regex' => TRUE, 'block' => TRUE, 'padding' => 2]],




        "<file>(.*?)<\/file>\n?" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<pre><code data-dw-file=\"true\">", "</code></pre>\n"], 'regex' => TRUE, 'block' => TRUE]],

        " {2}\* (.*?)\n" => ['state' => 'list-item', 'type' => 'li', 'class' => 'DW2HtmlList', 'action' => 'tree', 'extra' => ['replacement' => ["<li>", "</li>\n"], 'regex' => TRUE, 'container' => 'ul', 'block' => TRUE]],
        " {2}- (.*)\n" => ['state' => 'list-item', 'type' => 'li', 'class' => 'DW2HtmlList', 'action' => 'tree', 'extra' => ['replacement' => ["<li>", "</li>\n"], 'regex' => TRUE, 'container' => 'ol', 'block' => TRUE]],

        '**' => ['state' => 'bold', 'type' => 'bold', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<b>", "</b>"], 'exact' => TRUE]],

        '//' => ['state' => 'italic', 'type' => 'italic', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<i>", "</i>"], 'exact' => TRUE]],
        '__' => ['state' => 'underline', 'type' => 'underline', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<u>", "</u>"], 'exact' => TRUE]],

        // Aquest és monospace (inline)
        "^''(.*?)''$" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<code>", "</code>"], 'regex' => TRUE/*, 'replace' => TRUE*/]],

        ':table:(.*?):' => ['state' => 'link-special', 'type' => 'table', 'class' => 'DW2HtmlLinkSpecial', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'type' => 'table']],
        ':figure:(.*?):' => ['state' => 'link-special', 'type' => 'figure', 'class' => 'DW2HtmlLinkSpecial', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'type' => 'figure']],

        "<note>(.*?)<\/note>" => ['state' => 'note', 'type' => 'note', 'class' => 'DW2HtmlNote', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        // ALERTA[Xavi]: per ara ignorem els Readonly, el funcionament ha de ser similar als refs, afegint algun atribut
        // que bloquegi a tots els elements entre l'apertura i el tancament, serà necessari fer servir un stack
        // per ara ho ignorem perquè s'afegeixen 2 nodes: un amb el wioccl que el referència a la estructura i aquest
//        "[readonly-open]" => ['state' => 'readonly-open', 'type' => 'readonly', 'class' => 'DW2HtmlMarkup', 'action' => 'self-contained', 'extra' => ['replacement' => ["", ""], 'inline-block' => TRUE]],
//        "[readonly-close]" => ['state' => 'readonly-close', 'type' => 'readonly', 'class' => 'DW2HtmlMarkup', 'action' => 'self-contained', 'extra' => ['replacement' => ["", ""], 'inline-block' => TRUE]],
        "[readonly-open]" => ['state' => 'readonly-open', 'type' => 'readonly', 'class' => 'DW2HtmlReadonly', 'action' => 'self-contained', 'extra' => ['replacement' => ["<span data-wioccl-xtype=\"readonly\" data-wioccl-state='open' contenteditable='false'></span>", ""], 'inline-block' => TRUE]],
        "[readonly-close]" => ['state' => 'readonly-close', 'type' => 'readonly', 'class' => 'DW2HtmlReadonly', 'action' => 'self-contained', 'extra' => ['replacement' => ["<span data-wioccl-xtype=\"readonly\" data-readonly='close' data-wioccl-state='close' contenteditable='false'></span>", ""], 'inline-block' => TRUE]],


        "\\[ref=(.*?)\\]" => ['state' => 'ref-open', 'type' => 'wioccl', 'class' => 'DW2HtmlRef', 'action' => 'self-contained', 'extra' => ['replacement' => ["<span data-wioccl-ref=\"%d\" data-wioccl-state='open'></span>", ""], 'regex' => TRUE, 'inline-block' => TRUE]],
        "\[\\/ref=(.*?)\\]" => ['state' => 'ref-close', 'type' => 'wioccl', 'class' => 'DW2HtmlRef', 'action' => 'self-contained', 'extra' => ['replacement' => ["<span data-wioccl-ref=\"%d\" data-wioccl-state='close'></span>", ""], 'regex' => TRUE, 'inline-block' => TRUE]],


        // ALERTA, aquestes han d'anar al final de la llista de blocs

        // Tancament de paràgraf o Paràgraf buit
        "\n\n+" => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlParagraph', 'action' => 'close', 'extra' => ['regex' => TRUE, 'block' => TRUE]],


        "\n" => ['state' => 'close', 'type' => '', 'action' => 'close', 'extra' => ['regex' => TRUE, 'block' => TRUE]],



    ];

    protected static $instructionClass = "DW2HtmlInstruction";


    protected static function getPattern() {
        $pattern = '/(';

        foreach (static::$tokenPatterns as $statePattern => $data) {
            $pattern .= $statePattern . '|';
        }

        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')/ms';

//
//        if (static::$isInner) {
//            var_dump($pattern);
//        }

//        die();
        return $pattern;
    }

//     @override
    public static function getValue($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {


        foreach (static::$forceReplacements as $pattern => $replacementValue) {
            $text = preg_replace($pattern, $replacementValue, $text);
        }

        // Si hi ha estructura wioccl s'han d'elimiar les etiquetes d'apertura i tancament corresponent a nodes de tipus content
        // No es pot comprovar si s'ha demanat o no la estructura en aquest punt, automàticament eliminem qualsevol referència que es trobi

        // ALERTA: això solucionava el problema amb les taules i el wioccl, perquè el caràcter separador | a la cel·la final quedava separat: la apertura abans del caràcter i el tancament a la següent,
    //        $structure = WiocclParser::getStructure();
//
//        foreach ($structure as $node) {
//            if ($node->type == 'content') {
//                $pattern = '/\[ref=' . $node->id . '\]|\[\/ref=' . $node->id . '\]/ms';
//
//                $text = preg_replace($pattern, '', $text);
//            }
//
//        }


        return parent::getValue($text, $arrays, $dataSource, $resetables);
    }
}