<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocParser.php';

class Html2DWParser extends IocParser {
    // TODO: Extreure la base del WiocclParser i crear-la abstrac, de manera que no tinguem que sobrescriure totes
    // les propietats

    protected static $removeTokenPatterns = [
        '/<br />/'
//        '/:###/', '/###:/'
    ];

    protected static $tokenPatterns = [
        '<div>' => [
            'state' => 'open_div',
        ],
        '</div>' => [
            'state' => 'close_div',
        ],
        '<p>' => [
            'state' => 'open_p',
        ],
        '</p>' => [
            'state' => 'close_p',
        ],

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
        '<u>' => [
            'state' => 'open_underline',
        ],
        '</u>' => [
            'state' => 'close_underline',
        ],
//        '<del>' => [
//            'state' => 'open_strike',
//        ],
//        '</del>' => [
//            'state' => 'close_strike',
//        ],
//        '<strike>' => [
//            'state' => 'open_strike',
//        ],
//        '</strike>' => [
//            'state' => 'close_strike',
//        ],
        '<pre>' => [
            'state' => 'open_code',
        ],
        '</pre>' => [
            'state' => 'close_code',
        ],
        // TODO! Element amb autotancament, comprovar si funciona o s'ha d'implementar
        '<br />' => [
            'state' => 'content',
        ],
    ];

    protected static $tokenKey = [
        // aquests són els elements buits, sense cap atribut per processar.
        // TODO: ALERTA! El replacement del close no es fa servir perquè el replacement està consultant la etiqueta d'apertura!
        '<div>' => ['state' => 'open_div', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => "\n"]],
        '</div>' => ['state' => 'close_div', 'type' => 'bold', 'action' => 'close', 'extra' => ['replacement' => '\n\n']],
        '<p>' => ['state' => 'open_p', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => "\n"]],
        '</p>' => ['state' => 'close_p', 'type' => 'bold', 'action' => 'close', 'extra' => ['replacement' => '\n\n']],




        '<b>' => ['state' => 'open_bold', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '**']],
        '</b>' => ['state' => 'close_bold', 'type' => 'bold', 'action' => 'close', 'extra' => ['replacement' => '**']],
        '<i>' => ['state' => 'open_italic', 'type' => 'italic', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '//']],
        '</i>' => ['state' => 'close_italic', 'type' => 'italic', 'action' => 'close', 'extra' => ['replacement' => '//']],
        '<u>' => ['state' => 'open_underline', 'type' => 'underline', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '__']],
        '</u>' => ['state' => 'close_underline', 'type' => 'underline', 'action' => 'close', 'extra' => ['replacement' => '__']],
//        '<del>' => ['state' => 'open_strike', 'type' => 'strike', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '<del>']],
//        '</del>' => ['state' => 'close_strike', 'type' => 'strike', 'action' => 'close', 'extra' => ['replacement' => '</del>']],
        '<pre>' => ['state' => 'open_code', 'type' => 'code', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '<code>']],
        '</pre>' => ['state' => 'close_code', 'type' => 'code', 'action' => 'close', 'extra' => ['replacement' => '</code>\n\n']],
        // => ['state' => 'content', 'type' => 'newline', 'action' => '', 'extra' => ['replacement' => '\n']],

    ];
    protected static $instructionClass = "Html2DWInstruction";

//    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {
//
//        $instruction = new Html2DWInstruction($text, $arrays, $dataSource, $resetables);
//        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens
//        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
//    }

}