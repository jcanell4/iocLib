<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class DW2HtmlParser extends IocParser {


    protected static $removeTokenPatterns = [
//        '/\n/' // No es poden eliminar els salts perquè son imprescindibles per determinar el final dels contenidors/paràgraphs
    ];

    protected static $tokenPatterns = [
//        "$\n^" => [
//            'state' => 'newline',
//        ],

        "^={1,6}" => [
            'state' => 'header'
        ],

        "={1,6}\n" => [
            'state' => 'header'
        ],

        "^----\n" => [
            'state' => 'hr',
        ],


        // ALERTA! Aquest han d'anar sempre al final

        "\n" => [
            'state' => 'br',
        ],

        "^(.*)?\n" => [
            'state' => 'paragraph'
        ]
    ];


    protected static $tokenKey = [

        // ALERTA! La key es un string, no una expresió regular
        '$$BLOCK$$' => ['state' => 'paragraph', 'type' => 'p', 'class' => 'DW2HtmlMarkup', 'action' => 'container', 'extra' => ['replacement' => ["<p>", "</p>\n"], 'remove-new-line' => TRUE]],
//        '$$NEWLINE$$' => ['state' => 'newline', 'type' => 'br', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<br>\n"]],
        '----' => ['state' => 'hr', 'type' => 'hr', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<hr>\n"]],
        "======\n" => ['state' => 'header', 'type' => 'h1', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
        '======' => ['state' => 'header', 'type' => 'h1', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h1>", "</h1>\n"], 'exact' => TRUE]],
        "=====\n" => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
        '=====' => ['state' => 'header', 'type' => 'h2', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h2>", "</h2>\n"], 'exact' => TRUE]],
        "====\n" => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
        '====' => ['state' => 'header', 'type' => 'h3', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h3>", "</h3>\n"], 'exact' => TRUE]],
        "===\n" => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
        '===' => ['state' => 'header', 'type' => 'h4', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h4>", "</h4>\n"], 'exact' => TRUE]],
        "==\n" => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
        '==' => ['state' => 'header', 'type' => 'h5', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h5>", "</h5>\n"], 'exact' => TRUE]],
        "=\n" => ['state' => 'header', 'type' => 'h6', 'class' => 'DW2HtmlMarkup', 'action' => 'close'],
        '=' => ['state' => 'header', 'type' => 'h6', 'class' => 'DW2HtmlMarkup', 'action' => 'open', 'extra' => ['replacement' => ["<h6>", "</h6>\n"], 'exact' => TRUE]],
        "\n" => ['state' => 'br', 'type' => 'br', 'class' => 'DW2HtmlBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "<br>\n"]],
    ];
    protected static $instructionClass = "DW2HtmlInstruction";

//    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {
//
//        $instruction = new Html2DWInstruction($text, $arrays, $dataSource, $resetables);
//        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens
//        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
//    }

    protected static function getPattern() {
//        $pattern = '(';
        $pattern = '/(';

        foreach (static::$tokenPatterns as $statePattern => $data) {
            $pattern .= $statePattern . '|';
        }

//        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')';
        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')/m';

//        echo $pattern;
//        die();

        return $pattern;
    }
}