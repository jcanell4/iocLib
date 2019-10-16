<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class Html2DWParser extends IocParser {

    protected static $removeTokenPatterns = [
        "/\n/"
//        '/:###/', '/###:/'
    ];

    protected static $tokenPatterns = [
        '<div>' => [
            'state' => 'open_div',
        ],
        '</div>\n?' => [
            'state' => 'close_div',
        ],
        '<p>' => [
            'state' => 'open_p',
        ],
        "</p>\n?" => [
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
        '</pre>\n?' => [
            'state' => 'close_code',
        ],
        '<h1.*?>' => [
            'state' => 'open_h1',
        ],
        '</h1>\n?' => [
            'state' => 'close_h1',
        ],
        '<h2.*?>' => [
            'state' => 'open_h2',
        ],
        '</h2>\n?' => [
            'state' => 'close_h2',
        ],
        '<h3.*?>' => [
            'state' => 'open_h3',
        ],
        '</h3>\n?' => [
            'state' => 'close_h3',
        ],
        '<h4.*?>' => [
            'state' => 'open_h4',
        ],
        '</h4>\n?' => [
            'state' => 'close_h4',
        ],
        "<h5.*?>" => [
            'state' => 'open_h5',
        ],
        '</h5>\n?' => [
            'state' => 'close_h5',
        ],
        '</h6.*?>' => [
            'state' => 'close_h6',
        ],
        "<hr( \/)?>\n?" => [
            'state' => 'hr',
        ],
        "<br( \/)?>\n?" => [
            'state' => 'br',
        ],

        '\s*<ul>' => [
            'state' => 'open_list',
        ],
        "</ul>\n?" => [
            'state' => 'close_list',
        ],
        '\s*<li>' => [
            'state' => 'open_li',
        ],
        "</li>\n?" => [
            'state' => 'close_li',
        ],

    ];

    protected static $tokenKey = [
        // aquests són els elements buits, sense cap atribut per processar.
        // ALERTA! El replacement s'ha d'especificar al 'open', si es diferent al open i close es fa servir un array amb els dos elements (0 per open i 1 per close)
        '<div>' => ['state' => 'open_div', 'type' => 'div', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ["", "\n"]]],
        '</div>' => ['state' => 'close_div', 'type' => 'div', 'action' => 'close'],
        '<p>' => ['state' => 'open_p', 'type' => 'paragraph', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ["", "\n"]]],
        '</p>' => ['state' => 'close_p', 'type' => 'paragraph', 'action' => 'close'],
        '<b>' => ['state' => 'open_bold', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '**']],
        '</b>' => ['state' => 'close_bold', 'type' => 'bold', 'action' => 'close'],
        '<i>' => ['state' => 'open_italic', 'type' => 'italic', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '//']],
        '</i>' => ['state' => 'close_italic', 'type' => 'italic', 'action' => 'close'],
        '<u>' => ['state' => 'open_underline', 'type' => 'underline', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '__']],
        '</u>' => ['state' => 'close_underline', 'type' => 'underline', 'action' => 'close'],
        '<pre>' => ['state' => 'open_code', 'type' => 'code', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['<code>', '</code>']]],
        '</pre>' => ['state' => 'close_code', 'type' => 'code', 'action' => 'close'],
        '<h1' => ['state' => 'open_h1', 'type' => 'h1', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['======', "======\n"]]],
        '</h1>' => ['state' => 'close_h1', 'type' => 'h1', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h2' => ['state' => 'open_h2', 'type' => 'h2', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['=====', "=====\n"]]],
        '</h2>' => ['state' => 'close_h2', 'type' => 'h2', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h3' => ['state' => 'open_h3', 'type' => 'h3', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['====', "====\n"]]],
        '</h3>' => ['state' => 'close_h3', 'type' => 'h3', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h4' => ['state' => 'open_h4', 'type' => 'h4', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['===', "===\n"]]],
        '</h4>' => ['state' => 'close_h4', 'type' => 'h4', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h5' => ['state' => 'open_h5', 'type' => 'h5', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['==', "==\n"]]],
        '</h5>' => ['state' => 'close_h5', 'type' => 'h5', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h6' => ['state' => 'open_h6', 'type' => 'h6', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['==', "==\n"]]],
        '</h6>' => ['state' => 'close_h6', 'type' => 'h6', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<hr' => ['state' => 'hr', 'type' => 'hr', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "----\n"]],
        '<br' => ['state' => 'br', 'type' => 'br', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "\n"]], // ALERTA: a continuació de les marques de salt de línia que fica l'editor hi ha un \n, no cal afegir-lo


        '\s*<ul>' => ['state' => 'list', 'type' => 'ul', 'class' => 'Html2DWList', 'action' => 'open', 'extra' => ['container' => 'ul', 'regex' => TRUE]],
        '</ul>' => ['state' => 'list', 'type' => 'ul', 'action' => 'close'],

        '\s*<li>' => ['state' => 'list-item', 'type' => 'li', 'class' => 'Html2DWListItem', 'action' => 'open', 'extra' => ['replacement' => "\n", 'regex' => TRUE]],
        '</li>' => ['state' => 'list-item', 'type' => 'li', 'action' => 'close'],

    ];
    protected static $instructionClass = "Html2DWInstruction";

//    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {
//
//        $instruction = new Html2DWInstruction($text, $arrays, $dataSource, $resetables);
//        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens
//        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
//    }

}