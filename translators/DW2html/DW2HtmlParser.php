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


        "^----" => [
            'state' => 'hr',
        ],

        "={1,6}\n?" => [
            'state' => 'header'
        ],

//        // Qualsevol contingut + hr
//        "^(.*?)(^----\n)" => [
//            'state' => 'paragraph'
//        ],


        "<code.*?>(.*?)<\/code>\n" => [
            'state' => 'code',
        ],

        "<file>(.*?)<\/file>\n" => [
            'state' => 'code',
        ],


//        "^(?: {2})+[\*-](.*?)\n" => [
//            'state' => 'list-item'
//        ],

        "\*\*" => [
            'state' => 'bold'
        ],


    // ALERTA: Aquestes han d'anar sempre el final


        "\n\n+?" => [
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



        "<code.*?>(.*?)<\/code>\n" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<pre><code>", "</code></pre>\n"], 'regex' => TRUE, 'block' => TRUE]],

        "<file>(.*?)<\/file>\n" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<pre><code>", "</code></pre>\n"], 'regex' => TRUE, 'block' => TRUE]],

        '**' => ['state' => 'bold', 'type' => 'bold', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<b>", "</b>"], 'exact' => TRUE]],


        // ALERTA, aquestes han d'anar al final de la llista de blocs

        // Tancament de paràgraf o Paràgraf buit
        "\n\n+" => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlParagraph', 'action' => 'close', 'extra' => ['regex' => TRUE, 'block' => TRUE]],


        "\n" => ['state' => 'close', 'type' => '', 'action' => 'close', 'extra' => ['regex' => TRUE, 'block' => TRUE]],


        // Aquest és el inline
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