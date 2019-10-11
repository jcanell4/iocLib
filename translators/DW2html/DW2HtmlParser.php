<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class DW2HtmlParser extends IocParser {


    protected static $removeTokenPatterns = [
//        '/\n/' // No es poden eliminar els salts perquè son imprescindibles per determinar el final dels contenidors/paràgraphs
    ];

    protected static $tokenPatterns = [
        // Elements de block

        "={1,6}\n?" => [
            'state' => 'header'
        ],

        "^----\n" => [
            'state' => 'hr',
        ],


        "^(.*)?\n" => [
            'state' => 'paragraph'
        ],

        // Elements Inline
        "\*\*" => [
            'state' => 'bold'
        ],
        "\/\/" => [
            'state' => 'italic'
        ],
        "__" => [
            'state' => 'underline'
        ],

        // Elements restatns
        "\n" => [
            'state' => 'br',
        ],
    ];

    protected static $tokenKey = [

        // ALERTA! La key es un string, no una expresió regular
        '$$BLOCK$$' => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlMarkup', 'action' => 'container', 'extra' => ['replacement' => ["<p>", "</p>\n"], 'remove-new-line' => TRUE]],
        '**' => ['state' => 'bold', 'type' => 'bold', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<b>", "</b>"], 'exact' => TRUE]],
        '//' => ['state' => 'italic', 'type' => 'italic', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<i>", "</i>"], 'exact' => TRUE]],
        '__' => ['state' => 'underline', 'type' => 'underline', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<u>", "</u>"], 'exact' => TRUE]],
        '----' => ['state' => 'hr', 'type' => 'hr', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<hr>\n"]],


        '======' => ['state' => 'header', 'type' => 'h1', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h1>", "</h1>\n"], 'exact' => TRUE]],
        '=====' => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h2>", "</h2>\n"], 'exact' => TRUE]],
        '====' => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h3>", "</h3>\n"], 'exact' => TRUE]],
        '===' => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h4>", "</h4>\n"], 'exact' => TRUE]],
        '==' => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h5>", "</h5>\n"], 'exact' => TRUE]],
        '=' => ['state' => 'header', 'type' => 'h6', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h6>", "</h6>\n"], 'exact' => TRUE]],

        // Duplicats dels anteriors afegint salt de línia. Si no són exactes s'aplica el h6 a tots els casos, i si es exacte s'ignora el \n del final i es reemplaça per un br
        "======\n" => ['state' => 'header', 'type' => 'h1', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h1>", "</h1>\n"], 'exact' => TRUE]],
        "=====\n" => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h2>", "</h2>\n"], 'exact' => TRUE]],
        "====\n" => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h3>", "</h3>\n"], 'exact' => TRUE]],
        "===\n" => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h4>", "</h4>\n"], 'exact' => TRUE]],
        "==\n" => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h5>", "</h5>\n"], 'exact' => TRUE]],
        "=\n" => ['state' => 'header', 'type' => 'h6', 'class' => 'DW2HtmlMarkup', 'action' => 'open-close', 'extra' => ['replacement' => ["<h6>", "</h6>\n"], 'exact' => TRUE]],





        "\n" => ['state' => 'br', 'type' => 'br', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<br>\n"]],
    ];

    protected static $instructionClass = "DW2HtmlInstruction";


    protected static function getPattern() {
        $pattern = '/(';

        foreach (static::$tokenPatterns as $statePattern => $data) {
            $pattern .= $statePattern . '|';
        }

        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')/m';
        return $pattern;
    }
}