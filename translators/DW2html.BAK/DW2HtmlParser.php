<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class DW2HtmlParser extends IocParser {


    protected static $removeTokenPatterns = [
//        '/\n/' // No es poden eliminar els salts perquè son imprescindibles per determinar el final dels contenidors/paràgraphs
    ];

    protected static $tokenPatterns = [
        // Elements de block


        "^----\n?" => [
            'state' => 'hr',
        ],

//        // Qualsevol contingut + hr
//        "^(.*?)(^----\n)" => [
//            'state' => 'paragraph'
//        ],


        "<code.*?>\n(.*?)<\/code>\n$" => [
            'state' => 'code',
        ],

        "<file>\n(.*?)<\/file>\n$" => [
            'state' => 'code',
        ],


        "^(?: {2})+[\*-](.*?)\n" => [
            'state' => 'list-item'
        ],




        "^.*?\n\n+" => [
            'state' => 'paragraph'
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
        "----\n" => ['state' => 'hr', 'type' => 'hr', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<hr>\n"]],

        // P + HR <-- això no pot funcionar perquè el ---- no s'ha capturat, només es desa el contingut que no tindrà coincidencia i s'haurà de posar com a content.
//        "^(.*?)(?:----\n)"  => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlBlock', 'action' => 'container', 'extra' => ['replacement' => ["<p>", "</p>\n"], 'regex' => TRUE, 'block' => TRUE]],

        "<code.*?>\n(.*?)<\/code>\n$" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<pre><code>", "</code></pre>\n"], 'regex' => TRUE]],
        "<file>\n(.*?)<\/file>\n$" => ['state' => 'code', 'type' => 'code', 'class' => 'DW2HtmlCode', 'action' => 'self-contained', 'extra' => ['replacement' => ["<pre><code>", "</code></pre>"], 'regex' => TRUE]],

        " {2}\* (.*)$" => ['state' => 'list-item', 'type' => 'li', 'class' => 'DW2HtmlListItem', 'action' => 'list', 'extra' => ['replacement' => ["<li>", "</li>\n"], 'regex' => TRUE, 'container' => 'ul']],

        " {2}- (.*)$" => ['state' => 'list-item', 'type' => 'li', 'class' => 'DW2HtmlListItem', 'action' => 'list', 'extra' => ['replacement' => ["<li>", "</li>\n"], 'regex' => TRUE, 'container' => 'ol']],





        // ALERTA, això ha d'anar al final de la llista de blocs
        "^(.*?\n\n+)" => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlParagraph', 'action' => 'container', 'extra' => ['replacement' => ["<p>", "</p>\n"], 'regex' => TRUE, 'block' => TRUE]],






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
//        '======' => ['state' => 'header', 'type' => 'h1', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h1>", "</h1>\n"], 'exact' => TRUE]],
//        '=====' => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h2>", "</h2>\n"], 'exact' => TRUE]],
//        '====' => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h3>", "</h3>\n"], 'exact' => TRUE]],
//        '===' => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h4>", "</h4>\n"], 'exact' => TRUE]],
//        '==' => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h5>", "</h5>\n"], 'exact' => TRUE]],
//        '=' => ['state' => 'header', 'type' => 'h6', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h6>", "</h6>\n"], 'exact' => TRUE]],
//
//        // Duplicats dels anteriors afegint salt de línia. Si no són exactes s'aplica el h6 a tots els casos, i si es exacte s'ignora el \n del final i es reemplaça per un br
//        "======\n" => ['state' => 'header', 'type' => 'h1', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h1>", "</h1>\n"], 'exact' => TRUE]],
//        "=====\n" => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h2>", "</h2>\n"], 'exact' => TRUE]],
//        "====\n" => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h3>", "</h3>\n"], 'exact' => TRUE]],
//        "===\n" => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h4>", "</h4>\n"], 'exact' => TRUE]],
//        "==\n" => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h5>", "</h5>\n"], 'exact' => TRUE]],
//        "=\n" => ['state' => 'header', 'type' => 'h6', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h6>", "</h6>\n"], 'exact' => TRUE]],
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
        return $pattern;
    }

//    protected static function generateToken($tokenInfo) {
//       $token = parent::generateToken($tokenInfo);
//
//
//        // Si no s'ha trobat cap coincidencia i existeix un element generic (key = '$$BLOCK$$') s'aplica aquest
//        if (($token['state'] == 'none') && isset(static::$tokenKey['$$BLOCK$$'])) {
//
//            $value = static::$tokenKey['$$BLOCK$$'];
//            $token = $value;
//            // No te marques d'apertura ni tancament, per tant el valor será tot el capturat.
//            $token['value'] = $tokenInfo;
//            var_dump($tokenInfo);
//            die();
//
//        }
////
//        return $token;
//    }
}