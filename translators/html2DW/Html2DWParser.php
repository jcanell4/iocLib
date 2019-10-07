<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocParser.php';

class Html2DWParser extends IocParser {
    // TODO: Extreure la base del WiocclParser i crear-la abstrac, de manera que no tinguem que sobrescriure totes
    // les propietats

    protected static $removeTokenPatterns = [
//        '/:###/', '/###:/'
    ];

    protected static $tokenPatterns = [
        '<b>' => [
            'state' => 'open_bold',
        ],
        '</b>' => [
            'state' => 'close_bold',
        ],
        '<i>' => [
            'state' => 'open_italic',
        ],
        '</i>' => [
            'state' => 'close_italic',
        ],
    ];

    protected static $tokenKey = [
        '<b>' => ['state' => 'open_bold', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '**']],
        '</b>' => ['state' => 'close_bold', 'type' => 'bold', 'action' => 'close', 'extra' => ['replacement' => '**']],
        '<i>' => ['state' => 'open_italic', 'type' => 'italic', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '//']],
        '</i>' => ['state' => 'close_italic', 'type' => 'italic', 'action' => 'close', 'extra' => ['replacement' => '//']],

    ];
    protected static $instructionClass = "Html2DWInstruction";

//    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {
//
//        $instruction = new Html2DWInstruction($text, $arrays, $dataSource, $resetables);
//        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens
//        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
//    }

}