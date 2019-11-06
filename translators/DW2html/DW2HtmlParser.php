<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class DW2HtmlParser extends IocParser {

    public static $defaultContainer = ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlParagraph', 'action' => 'open', 'extra' => ['replacement' => ["<p>", "</p>\n"], 'regex' => TRUE, 'block' => TRUE]];

    protected static $removeTokenPatterns = [
//        '/\n/' // No es poden eliminar els salts perquè son imprescindibles per determinar el final dels contenidors/paràgraphs
    ];

    protected static $tokenPatterns = [
        // Elements de block


        "^----$" => [
            'state' => 'hr',
        ],

        "={1,6}\n?" => [
            'state' => 'header'
        ],

//        // Qualsevol contingut + hr
//        "^(.*?)(^----\n)" => [
//            'state' => 'paragraph'
//        ],


        /*        "<code.*?>\n(.*?)<\/code>\n$" => [*/
//            'state' => 'code',
//        ],
//
//        "<file>\n(.*?)<\/file>\n$" => [
//            'state' => 'code',
//        ],


//        "^(?: {2})+[\*-](.*?)\n" => [
//            'state' => 'list-item'
//        ],


        "\n*?\n\n" => [
            'state' => 'paragraph'
        ],


        "\n" => [
            'state' => 'close'
        ],

//        "''(.*?)''" => [
//            'state' => 'code', // inline
//        ],
//

//
//
//        "={1,6}\n?" => [
//            'state' => 'header'
//        ],
//

//
//

//
//        // TODO ALERTA: No implementat encara! S'inclouen les marques ^ i | a la captura per poder determinar si es TH o TD
////        "^([\^|\|].*?[\^|\|]$)" => [
////            'state' => 'row'
////        ],
//
//        "\[{2}(.*?)\]{2}" => [
//            'state' => 'link'
//        ],
//
//        "{{(.*?)}}" => [
//            'state' => 'image'
//        ],
//
//        // Elements Inline, s'han de comprovar després dels elements de block
//        "^\*\*(.*?)$" => [ // Especial, apertura d'element inline al principi de la línia
//            'state' => 'bold'
//        ],
//
//        "\*\*" => [
//            'state' => 'bold'
//        ],
//
//        "^\/\/(.*?)$" => [ // Especial, apertura d'element inline al principi de la línia
//            'state' => 'italic'
//        ],
//
//        "\/\/" => [
//            'state' => 'italic'
//        ],
//
//        "^__(.*?)$" => [ // Especial, apertura d'element inline al principi de la línia
//            'state' => 'underline'
//        ],
//
//        "__" => [
//            'state' => 'underline'
//        ],
//
//        // Elements restatns
////        "^\n" => [
////            'state' => 'br',
////        ],
//
//        "\/\/ " => [
//            'state' => 'br',
//        ],
//
//        "\/\/\n" => [
//            'state' => 'br',
//        ],
//
//        "^.*?\n" => [
//            'state' => 'paragraph'
//        ],
//
//        "^\n" => [
//            'state' => 'ignore',
//        ],

    ];

    // ALERTA! La key es un string, no una expresió regular
    protected static $tokenKey = [

        // ALERTA! no ha de ser regex, si es posa com a regex es pot considerar match de les captures multilínia
        "----" => ['state' => 'hr', 'type' => 'hr', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'open', 'extra' => ['replacement' => "<hr>", 'block' => TRUE]],

        // El close ha d'anar abans perquè si no es detecta com a open <-- TODO: posar-ho com regex
        "={1,6}\n" => ['state' => 'header', 'type' => 'header', 'class' => 'DW2HtmlHeader', 'action' => 'close', 'extra' => ['regex' => TRUE]],
        '={1,6}' => ['state' => 'header', 'type' => 'header', 'class' => 'DW2HtmlHeader', 'action' => 'open', 'extra' => ['block' => TRUE, 'regex' => TRUE]],

//        "======\n" => ['state' => 'header', 'type' => 'h1', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
//        '======' => ['state' => 'header', 'type' => 'h1', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h1>", "</h1>\n"], 'block' => TRUE]],

//        "=====\n" => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
//        '=====' => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h2>", "</h2>\n"], 'block' => TRUE]],
//
//        "====\n" => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
//        '====' => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h3>", "</h3>\n"], 'block' => TRUE]],
//
//        "==\n" => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
//        '==' => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h4>", "</h4>\n"], 'block' => TRUE]],
//
//        "=\n" => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
//        '=' => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h5>", "</h5>\n"], 'block' => TRUE]],


//        '=====' => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h2>", "</h2>\n"], 'exact' => TRUE]],
//        '====' => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h3>", "</h3>\n"], 'exact' => TRUE]],
//        '===' => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h4>", "</h4>\n"], 'exact' => TRUE]],
//        '==' => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h5>", "</h5>\n"], 'exact' => TRUE]],
//        '=' => ['state' => 'header', 'type' => 'h6', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h6>", "</h6>\n"], 'exact' => TRUE]],

        // Duplicats dels anteriors afegint salt de línia. Si no són exactes s'aplica el h6 a tots els casos, i si es exacte s'ignora el \n del final i es reemplaça per un br

//        "=====\n" => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h2>", "</h2>\n"], 'exact' => TRUE]],
//        "====\n" => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h3>", "</h3>\n"], 'exact' => TRUE]],
//        "===\n" => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h4>", "</h4>\n"], 'exact' => TRUE]],
//        "==\n" => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h5>", "</h5>\n"], 'exact' => TRUE]],
//        "=\n" => ['state' => 'header', 'type' => 'h6', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h6>", "</h6>\n"], 'exact' => TRUE]],


        // ALERTA, això ha d'anar al final de la llista de blocs
            "^(\n*?\n\n+)" => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlParagraph', 'action' => 'close', 'extra' => ['replacement' => ["<p>", "</p>\n"], 'regex' => TRUE, 'block' => TRUE]],

        "\n" => ['state' => 'close', 'type' => '', 'action' => 'close', 'extra' => ['regex' => TRUE, 'block' => TRUE]],




//        "^''(.*?)''$" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<code>", "</code>"], 'regex' => TRUE/*, 'replace' => TRUE*/]],
//
//
//
//
////        '$$BLOCK$$' => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlBlock', 'action' => 'container', 'extra' => ['replacement' => ["<p>", "</p>"]]],
//
//        // TODO: Això de l'start s'ha d'aplicar a tots els elements inline, cercar una altra solució
////        '^\*\*(.*?)$' => ['state' => 'bold', 'type' => 'bold', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<b>", "</b>"], 'regex' => TRUE, 'start' => TRUE]],
//
//
//
//        '**' => ['state' => 'bold', 'type' => 'bold', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<b>", "</b>"], 'exact' => TRUE]],
//        '//' => ['state' => 'italic', 'type' => 'italic', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<i>", "</i>"], 'exact' => TRUE]],
//        '__' => ['state' => 'underline', 'type' => 'underline', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<u>", "</u>"], 'exact' => TRUE]],
//
//
//        "\[{2}(.*?)\]{2}" => ['state' => 'link', 'type' => 'a', 'class' => 'DW2HtmlLink', 'action' => 'self-contained', 'extra' => ['replacement' => ["<a ", "</a>"], 'regex' => TRUE]],
//
//        "{{(.*?)}}" => ['image' => 'link', 'type' => 'img', 'class' => 'DW2HtmlImage', 'action' => 'self-contained', 'extra' => ['replacement' => ["<img ", " />"], 'regex' => TRUE]],
//
//

//
////        "\n" => ['state' => 'br', 'type' => 'br', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<br>\n"]],
//
//        "// " => ['state' => 'br', 'type' => 'br', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<br>\n"]],
//        "//\n" => ['state' => 'br', 'type' => 'br', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<br>\n"]],
//
//        "\n" => ['state' => 'ignore', 'type' => 'none', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => ""], 'exact' => TRUE],
//
//        "^(.*?)\n" => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlBlock', 'action' => 'container', 'extra' => ['replacement' => ["<p>", "</p>\n"], 'regex' => TRUE]],

    ];

    protected static $instructionClass = "DW2HtmlInstruction";


    protected static function getPattern() {
        $pattern = '/(';

        foreach (static::$tokenPatterns as $statePattern => $data) {
            $pattern .= $statePattern . '|';
        }

        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')/ms';

//
//        var_dump($pattern);
//        die();
        return $pattern;
    }


}