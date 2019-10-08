<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class Html2DWParser extends IocParser {
    // TODO: Extreure la base del WiocclParser i crear-la abstrac, de manera que no tinguem que sobrescriure totes
    // les propietats

    protected static $removeTokenPatterns = [
        '/<br *.*?[^\\\\]>/'
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
        '<pre>' => [
            'state' => 'open_code',
        ],
        '</pre>' => [
            'state' => 'close_code',
        ],
        '<h1 *.*?[^\\\\]>(\n)?' => [
            'state' => 'open_h1',
        ],
        '</h1>' => [
            'state' => 'close_h1',
        ],
        '<h2 *.*?[^\\\\]>(\n)?' => [
            'state' => 'open_h2',
        ],
        '</h2>' => [
            'state' => 'close_h2',
        ],
        '<h3 *.*?[^\\\\]>(\n)?' => [
            'state' => 'open_h3',
        ],
        '</h3>' => [
            'state' => 'close_h3',
        ],
        '<h4 *.*?[^\\\\]>(\n)?' => [
            'state' => 'open_h4',
        ],
        '</h4>' => [
            'state' => 'close_h4',
        ],
        '<h5 *.*?[^\\\\]>(\n)?' => [
            'state' => 'open_h5',
        ],
        '</h5>' => [
            'state' => 'close_h5',
        ],
        '<h6 *.*?[^\\\\]>(\n)?' => [
            'state' => 'open_h6',
        ],
        '</h6>' => [
            'state' => 'close_h6',
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
        '<pre>' => ['state' => 'open_code', 'type' => 'code', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '<code>']],
        '</pre>' => ['state' => 'close_code', 'type' => 'code', 'action' => 'close', 'extra' => ['replacement' => '</code>\n\n']],
        '<h1' => ['state' => 'open_h1', 'type' => 'h1', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '======']],
        '</h1>' => ['state' => 'close_h1', 'type' => 'h1', 'class' => 'Html2DWMarkup', 'action' => 'close', 'extra' => ['replacement' => '======']],
        '<h2' => ['state' => 'open_h2', 'type' => 'h2', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '=====']],
        '</h2>' => ['state' => 'close_h2', 'type' => 'h2', 'class' => 'Html2DWMarkup', 'action' => 'close', 'extra' => ['replacement' => '=====']],
        '<h3' => ['state' => 'open_h3', 'type' => 'h3', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '====']],
        '</h3>' => ['state' => 'close_h3', 'type' => 'h3', 'class' => 'Html2DWMarkup', 'action' => 'close', 'extra' => ['replacement' => '====']],
        '<h4' => ['state' => 'open_h4', 'type' => 'h4', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '===']],
        '</h4>' => ['state' => 'close_h4', 'type' => 'h4', 'class' => 'Html2DWMarkup', 'action' => 'close', 'extra' => ['replacement' => '===']],
        '<h5' => ['state' => 'open_h5', 'type' => 'h5', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '==']],
        '</h5>' => ['state' => 'close_h5', 'type' => 'h5', 'class' => 'Html2DWMarkup', 'action' => 'close', 'extra' => ['replacement' => '==']],
        '<h6' => ['state' => 'open_h6', 'type' => 'h6', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '=']],
        '</h6>' => ['state' => 'close_h6', 'type' => 'h6', 'class' => 'Html2DWMarkup', 'action' => 'close', 'extra' => ['replacement' => '=']],


    ];
    protected static $instructionClass = "Html2DWInstruction";

//    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {
//
//        $instruction = new Html2DWInstruction($text, $arrays, $dataSource, $resetables);
//        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens
//        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
//    }

}