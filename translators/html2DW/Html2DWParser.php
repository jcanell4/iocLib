<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class Html2DWParser extends IocParser {

    protected static $removeTokenPatterns = [
//        "/\n/"
        "/<div class=\"no-render.*?<\/div>/ms",
        "/<span class=\"no-render.*?<\/span>/ms",

        // Aquests es poden eliminar perquè hi ha una instrucció wioccl associada amb refid
        "/<span data-wioccl-state=\"open\"><\/span>/",
        "/<span data-wioccl-state=\"close\"><\/span>/"
    ];

    protected static $tokenPatterns = [



        "<newcontent>.*?<\/newcontent>" => [
            'state' => 'newcontent',
        ],

        '<div>(<br ?\/>)*<\/div>' => [
            'state' => 'paragraph',
        ],

        // ALERTA! Sempre ha de ser el primer atribut el div: data-dw-lateral
        '<div class="imgb.*?" data-dw-lateral.*?<\/div><\/div>' => [
            'state' => 'image-lateral'
        ],

        '<div class="iocgif".*?>.*?<\/div>' => [
            'state' => 'gif'
        ],
        '<div class="iocinclude".*?>*?<\/div>' => [
            'state' => 'include'
        ],

        // ALERTA! Sempre ha de ser el primer atribut el div: data-dw-box però els navegadors reordenan els atributs i posen primer el class si existeix
        '<div class="ioc(?:table|figure).*?" data-dw-box=.*?>\n?<div.*?iocinfo.*?>.*?<\/div>\n?.*?<\/div>' => [
            /*        '<div(?: class=".*?")? data-dw-box=.*?>\n?<div.*?iocinfo.*?>.*?<\/div>\n?.*?<\/div>' => [*/
            'state' => 'box',
        ],


        '<table.*=?>.*?<\/table>' => [
            'state' => 'table',
        ],

        // ALERTA! Es trobava al principi, mogut perquè si no no captura el wioccl de les files de les taules
        // ALERTA! al BasicEditorSubclass s'elimina l'atribut class
        '<span data-wioccl-ref="\d+" data-wioccl-state="open"><\/span>' => [
            'state' => 'wioccl'
        ],

        "<div class=\"ioc(?:text|textl|example|note|reference|important|quote).*?\" data-dw-box-text=\"(.*?)\".*?>(.*?)<\/div><\/div>\n?" => [
            /*        '<div(?: class=".*?")? data-dw-box-text="(.*?)".*?>(.*?)<\/div><\/div>' => [*/
            'state' => 'box-text',
        ],

        '<div class="ioc-quiz".*?(<\/div><\/div><\/div>|<\/pre><\/div><\/div>)' => [
            'state' => 'quiz'
        ],


        '<div(?: class="")?(?: contenteditable="false")? data-dw-block="(.*?)".*?>.*?<\/div>' => [
            'state' => 'block',
        ],


        '<span class="ioc-comment-block".*?<span data-delete-block.*?<\/span>' => [
            'state' => 'note',
        ],

        '<p( .*?)?>' => [
            'state' => 'open_p',
        ],

        '&nbsp;' => [
            'state' => 'space',
        ],

        "\n?<\/p>\n?" => [
            'state' => 'close_p',
        ],

        // L'editor afegeix els <div> com a paràgrafs normals, però llavors peta quan es detecten altres divs sense atributs utilitzats amb els plugins
//        '\n?<\/div>' => [
//            'state' => 'close_p',
//        ],


        '<b ?.*?>' => [
            'state' => 'open_bold',
        ],
        '<\/b>' => [
            'state' => 'close_bold',
        ],
        '<i>' => [
            'state' => 'open_italic',
        ],
        '<\/i>' => [
            'state' => 'close_italic',
        ],
        '<u>' => [
            'state' => 'open_underline',
        ],
        '<\/u>' => [
            'state' => 'close_underline',
        ],

        '<pre.*?>\n?<code.*?>(.*?)<\/code>\n?<\/pre>' => [
            'state' => 'code',
        ],
//
        '<code>(.*?)<\/code>' => [
            'state' => 'monospace',
        ],

        /*        '<div class="ioc-comment-block" data-ioc-comment=".*?".*?>(.*?)<\/div data-ioc-comment="">' => [*/
//            'state' => 'note',
//        ],


        '<h1.*?>' => [
            'state' => 'open_h1',
        ],
        '<\/h1>\n?' => [
            'state' => 'close_h1',
        ],
        '<h2.*?>' => [
            'state' => 'open_h2',
        ],
        "\n?<\/h2>\n?" => [
            'state' => 'close_h2',
        ],
        '<h3.*?>' => [
            'state' => 'open_h3',
        ],
        "\n?<\/h3>\n?" => [
            'state' => 'close_h3',
        ],
        '<h4.*?>' => [
            'state' => 'open_h4',
        ],
        "\n?<\/h4>\n?" => [
            'state' => 'close_h4',
        ],
        "<h5.*?>" => [
            'state' => 'open_h5',
        ],
        "\n?<\/h5>\n?" => [
            'state' => 'close_h5',
        ],

        "<hr( \/)?>" => [
            'state' => 'hr',
        ],
        "\n?<br( \/)?>" => [
            'state' => 'br',
        ],

        '<ul.*?>' => [
            'state' => 'open_list',
        ],
        "<\/ul>" => [ // el salt de línia s'ha d'eliminar perquè aquesta etiqueta al DW és eliminada
            'state' => 'close_list',
        ],

        '<ol.*?>' => [
            'state' => 'open_list',
        ],
        "<\/ol>" => [ // el salt de línia s'ha d'eliminar perquè aquesta etiqueta al DW és eliminada
            'state' => 'close_list',
        ],

        '<li>' => [
            'state' => 'open_li',
        ],
        "<\/li>\n?" => [
            'state' => 'close_li',
        ],

        '<a ?.*?>.*?<\/a>' => [
            'state' => 'link',
        ],

        '<img.*?>' => [
            'state' => 'image',
        ],




    ];

    protected static $tokenKey = [


        "<newcontent>(.*?)<\/newcontent>" => ['mode' => 'block', 'state' => 'newcontent', 'type' => 'newcontent', 'class' => 'Html2DWNewContent', 'action' => 'self-contained', 'extra' => ['replacement' => ["<newcontent>", "</newcontent>"], 'regex' => TRUE]],

        "'<div>(<br ?\/>)*<\/div>'" => ['mode' => 'block', 'state' => 'paragraph', 'type' => 'paragraph', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "\\\\ ", 'regex' => TRUE]],

        '<div class="imgb.*?" data-dw-lateral="(.*?)".*?>(<img.*?>)(.*?)<\/div><\/div>' => ['mode' => 'block','state' => 'image-lateral', 'type' => 'image', 'class' => 'Html2DWLateral', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<div class="iocgif".*?><img.*?><\/div>' => ['mode' => 'block', 'state' => 'gif', 'type' => 'gif', 'class' => 'Html2DWImageGIF', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<div class="iocinclude".*?>*?<\/div>' => ['mode' => 'block', 'state' => 'include', 'type' => 'include', 'class' => 'Html2DWInclude', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<div class="(?:ioctable|iocfigure).*?" data-dw-box="(.*?)".*?>\n?<div.*?iocinfo.*?>(.*?)<\/div>\n?(.*?)<\/div>' => ['mode' => 'block', 'state' => 'box', 'type' => 'box', 'class' => 'Html2DWBox', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<table.*=?>(.*?)<\/table>' => ['mode' => 'block', 'state' => 'table', 'type' => 'table', 'class' => 'Html2DWTable', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        // ALERTA! es trobava adalt
        '<span data-wioccl-ref="(\d+)" data-wioccl-state="open"><\/span>' => ['state' => 'wioccl', 'type' => 'wioccl', 'class' => 'Html2DWWioccl', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<div(?: class="")?(?: contenteditable="false")? data-dw-block="sound".*?>.*?<\/div>' => ['mode' => 'block', 'state' => 'sound', 'type' => 'sound', 'class' => 'Html2DWSound', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<div(?: class="")?(?: contenteditable="false")? data-dw-block="video".*?>.*?<\/div>' => ['mode' => 'block', 'state' => 'video', 'type' => 'video', 'class' => 'Html2DWMedia', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        "<div class=\"ioc(?:text|textl|example|note|reference|important|quote).*?\" data-dw-box-text=\"(.*?)\".*?>(.*?)<\/div><\/div>\n?" => ['mode' => 'block', 'state' => 'box', 'type' => 'text', 'class' => 'Html2DWBoxText', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<div class="ioc-quiz".*?(<\/div><\/div><\/div>|<\/pre><\/div><\/div>)' => ['mode' => 'block', 'state' => 'quiz', 'type' => 'quiz', 'class' => 'Html2DWQuiz', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

/*        '<div class="ioc-quiz".*?><\/table><\/div><\/div><\/div>' => ['state' => 'quiz', 'type' => 'quiz', 'class' => 'Html2DWQuiz', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],*/



        '<span class="ioc-comment-block".*?>(.*?)<span data-delete-block.*?<\/span>' => ['mode' => 'block', 'state' => 'note', 'type' => 'note', 'class' => 'Html2DWNote', 'action' => 'self-contained', 'extra' => ['replacement' => ["<note>", "</note>"], 'regex' => TRUE]],


        '<a ?(.*?)>.*?<\/a>' => ['mode' => 'inline', 'state' => 'link', 'type' => 'a', 'class' => 'Html2DWLink', 'action' => 'self-contained', 'extra' => ['replacement' => ["[[", "]]"], 'regex' => TRUE]],

        '^<p( .*?)?>' => ['mode' => 'block', 'state' => 'open_p', 'type' => 'paragraph', 'class' => 'Html2DWParagraph', 'action' => 'open', 'extra' => ['replacement' => ["", "\n\n"], 'regex' => TRUE]],


        "\n?<\/p>\n?" => ['mode' => 'block', 'state' => 'close_p', 'type' => 'paragraph', 'action' => 'close', 'extra' => ['regex' => TRUE]],


        "<pre.*?>\n?<code.*?>(.*?)<\/code>\n?<\/pre>" => ['mode' => 'block', 'state' => 'code', 'type' => 'code', 'class' => 'Html2DWCode', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],


        // ALERTA: aquest ha d'anar abans que el <b perque es barrejan
        '\n?<br( \/)?>' => ['mode' => 'block', 'state' => 'br', 'type' => 'br', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "\\\\ ", 'regex' => TRUE]],


        '^<b ?.*?' => ['mode' => 'inline', 'state' => 'open_bold', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '**', 'regex' => TRUE]],
        '</b>' => ['mode' => 'inline', 'state' => 'close_bold', 'type' => 'bold', 'action' => 'close'],
        '<i>' => ['mode' => 'inline', 'state' => 'open_italic', 'type' => 'italic', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '//']],
        '</i>' => ['mode' => 'inline', 'state' => 'close_italic', 'type' => 'italic', 'action' => 'close'],
        '<u>' => ['mode' => 'inline', 'state' => 'open_underline', 'type' => 'underline', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '__']],
        '</u>' => ['mode' => 'inline', 'state' => 'close_underline', 'type' => 'underline', 'action' => 'close'],


        '<code>(.*?)<\/code>' => ['mode' => 'block', 'state' => 'code', 'type' => 'code', 'class' => 'Html2DWMonospace', 'action' => 'self-contained', 'extra' => ['replacement' => "''", 'regex' => TRUE]],

        "<h1" => ['mode' => 'block', 'state' => 'open_h1', 'type' => 'h1', 'class' => 'Html2DWHeader', 'action' => 'open', 'extra' => ['replacement' => ['======', "======\n"], 'regex' => TRUE]],
        "<\/h1>\n?" => ['mode' => 'block', 'state' => 'close_h1', 'type' => 'h1', 'class' => 'Html2DWHeader', 'action' => 'close', 'extra' => ['regex' => TRUE]],
        "<h2" => ['mode' => 'block', 'state' => 'open_h2', 'type' => 'h2', 'class' => 'Html2DWHeader', 'action' => 'open', 'extra' => ['replacement' => ['=====', "=====\n"]]],
        "<\/h2>\n?" => ['mode' => 'block', 'state' => 'close_h2', 'type' => 'h2', 'class' => 'Html2DWHeader', 'action' => 'close', 'extra' => ['regex' => TRUE]],
        '<h3' => ['mode' => 'block', 'state' => 'open_h3', 'type' => 'h3', 'class' => 'Html2DWHeader', 'action' => 'open', 'extra' => ['replacement' => ['====', "====\n"]]],
        "<\/h3>\n?" => ['mode' => 'block', 'state' => 'close_h3', 'type' => 'h3', 'class' => 'Html2DWHeader', 'action' => 'close', 'extra' => ['regex' => TRUE]],
        "<h4" => ['mode' => 'block', 'state' => 'open_h4', 'type' => 'h4', 'class' => 'Html2DWHeader', 'action' => 'open', 'extra' => ['replacement' => ['===', "===\n"]]],
        "<\/h4>\n?" => ['mode' => 'block', 'state' => 'close_h4', 'type' => 'h4', 'class' => 'Html2DWHeader', 'action' => 'close', 'extra' => ['regex' => TRUE]],
        "<h5" => ['mode' => 'block', 'state' => 'open_h5', 'type' => 'h5', 'class' => 'Html2DWHeader', 'action' => 'open', 'extra' => ['replacement' => ['==', "==\n"]]],
        "<\/h5>\n?" => ['mode' => 'block', 'state' => 'close_h5', 'type' => 'h5', 'class' => 'Html2DWHeader', 'action' => 'close', 'extra' => ['regex' => TRUE]],

        '<hr( \/)?>' => ['mode' => 'block', 'state' => 'hr', 'type' => 'hr', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['regex' => TRUE, 'replacement' => "----"]],
        '&nbsp;' => ['mode' => 'block', 'state' => 'hr', 'type' => 'hr', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => " "]],


        '<li>' => ['mode' => 'block', 'state' => 'list-item', 'type' => 'li', 'class' => 'Html2DWListItem', 'action' => 'open', 'extra' => ['replacement' => "", 'regex' => TRUE]],
        "</li>" => ['mode' => 'block', 'state' => 'list-item', 'type' => 'li', 'action' => 'close'],


        '<ul.*?>' => ['mode' => 'block', 'state' => 'list', 'type' => 'ul', 'class' => 'Html2DWList', 'action' => 'open', 'extra' => ['container' => 'ul', 'regex' => TRUE]],
        '</ul>' => ['mode' => 'block', 'state' => 'list', 'type' => 'ul', 'action' => 'close'],

        '<ol.*?>' => ['mode' => 'block', 'state' => 'list', 'type' => 'ol', 'class' => 'Html2DWList', 'action' => 'open', 'extra' => ['container' => 'ol', 'regex' => TRUE]],
        '</ol>' => ['mode' => 'block', 'state' => 'list', 'type' => 'ol', 'action' => 'close'],


        '<img' => ['mode' => 'inline', 'state' => 'image', 'type' => 'image', 'class' => 'Html2DWImage', 'action' => 'self-contained', 'extra' => ['replacement' => ['{{', '}}']]],

    ];

    protected static $instructionClass = "Html2DWInstruction";

//    protected static function getPattern() {
//        $pattern = '/';
//
//        foreach (static::$tokenPatterns as $statePattern => $data) {
//            $pattern .= $statePattern . '|';
//        }
//
//        $pattern = substr($pattern, 0, strlen($pattern) - 1) . '/ms';
//
//        var_dump($pattern);
//
////
////        var_dump($pattern);
////        die();
//        return $pattern;
//    }


    protected static function getPattern() {
        $pattern = '/(';
//        $pattern = '(';

        foreach (static::$tokenPatterns as $statePattern => $data) {
            $pattern .= $statePattern . '|';
        }

        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')/ms';
//        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')';

//
//        var_dump($pattern);

        return $pattern;
    }

//    protected static function tokenize($rawText) {
//        $tokens = parent::tokenize($rawText);
//        var_dump($tokens);
//        return $tokens;
//    }


    // @override

    public static function getValue($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {

        // Reemplacem els nbsp per espais normals
        $text = str_replace(' ', ' ', $text);

        foreach (static::$forceReplacements as $pattern => $replacementValue) {
            $text = preg_replace($pattern, $replacementValue, $text);
        }

        $value = parent::getValue($text, $arrays, $dataSource, $resetables);

        return html_entity_decode($value);
    }

    public static $structure;

    public static function initializeStructure($structureData) {
        static::$structure = [];

        foreach ($structureData as $data) {
            if (!is_array($data)) {
                // pot ser la propietat next que indica el ID del següent element a afegir
                continue;
            }
            static::$structure[$data['id']] = new WiocclStructureItem(static::$structure, $data);
        }

    }
}