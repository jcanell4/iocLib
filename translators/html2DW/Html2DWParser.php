<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class Html2DWParser extends IocParser {

    protected static $removeTokenPatterns = [
//        "/\n/"
        "/<div class=\"no-render.*?<\/div>/ms"
    ];

    protected static $tokenPatterns = [

        // Els boxes s'ha de ficar abans

        // ALERTA! Sempre ha de ser el primer atribut el div: data-dw-lateral
        '<div(?: class=".*?")? data-dw-lateral.*?<\/div><\/div>' => [
            'state' => 'image-lateral'
        ],

        // ALERTA! Sempre ha de ser el primer atribut el div: data-dw-box però els navegadors reordenan els atributs i posen primer el class si existeix
        '<div(?: class=".*?")? data-dw-box=.*?>\n?<div.*?iocinfo.*?>.*?<\/div>\n?.*?<\/div>' => [
            'state' => 'box',
        ],

        '<div(?: contenteditable="false") data-dw-block="(.*?)".*?>.*?<\/div>' => [
            'state' => 'block',
        ],

        '<table.*=?>.*?<\/table>' => [
            'state' => 'table',
        ],

        '<p>' => [
            'state' => 'open_p',
        ],

        '&nbsp;' => [
            'state' => 'space',
        ],

        "\n<\/p>" => [
            'state' => 'close_p',
        ],
        '<\/p>' => [
            'state' => 'close_p',
        ],


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

        '<pre>\n?<code.*?>(.*?)<\/code>\n?<\/pre>' => [
            'state' => 'code',
        ],

        '<code>(.*?)<\/code>' => [ // TODO: Això fa que peti però no enten perqué, per altra banda ha de ser self-contained
            'state' => 'code',
        ],

        '<h1.*?>' => [
            'state' => 'open_h1',
        ],
        '<\/h1>' => [
            'state' => 'close_h1',
        ],
        '<h2.*?>' => [
            'state' => 'open_h2',
        ],
        '<\/h2>' => [
            'state' => 'close_h2',
        ],
        '<h3.*?>' => [
            'state' => 'open_h3',
        ],
        '<\/h3>' => [
            'state' => 'close_h3',
        ],
        '<h4.*?>' => [
            'state' => 'open_h4',
        ],
        '<\/h4>' => [
            'state' => 'close_h4',
        ],
        "<h5.*?>" => [
            'state' => 'open_h5',
        ],
        '<\/h5>' => [
            'state' => 'close_h5',
        ],

        "<hr( \/)?>" => [
            'state' => 'hr',
        ],
        "<br( \/)?>" => [
            'state' => 'br',
        ],

        '<ul>' => [
            'state' => 'open_list',
        ],
        "<\/ul>" => [ // el salt de línia s'ha d'eliminar perquè aquesta etiqueta al DW és eliminada
            'state' => 'close_list',
        ],

        '<ol>' => [
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

        '<img.*?\/>' => [
            'state' => 'image',
        ],
    ];

    protected static $tokenKey = [


        '<div(?: class=".*?")? data-dw-lateral="(.*?)".*?>(<img.*?\/>).*?<\/div><\/div>' => ['state' => 'image-lateral', 'type' => 'image', 'class' => 'Html2DWLateral', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<div(?: class=".*?")? data-dw-box="(.*?)".*?>\n?<div.*?iocinfo.*?>(.*?)<\/div>\n?(.*?)<\/div>' => ['state' => 'box', 'type' => 'box', 'class' => 'Html2DWBox', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<div(?: contenteditable="false") data-dw-block="(.*?)".*?>.*?<\/div>' => ['state' => 'sound', 'type' => 'sound', 'class' => 'Html2DWSound', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],


        '<table.*=?>(.*?)<\/table>' => ['state' => 'table', 'type' => 'table', 'class' => 'Html2DWTable', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<a ?(.*?)>.*?<\/a>' => ['state' => 'link', 'type' => 'a', 'class' => 'Html2DWLink', 'action' => 'self-contained', 'extra' => ['replacement' => ["[[", "]]"], 'regex' => TRUE]],

        '<p>' => ['state' => 'open_p', 'type' => 'paragraph', 'class' => 'Html2DWParagraph', 'action' => 'open', 'extra' => ['replacement' => ["", "\n"]]], // si posem un salt de línia a l'apertura s'afegeix un salt de línia quan es fa un tancament --> es tanca després de **negreta** i després de //cursiva//

        "\n?<\/p>" => ['state' => 'close_p', 'type' => 'paragraph', 'action' => 'close', 'extra' => ['regex' => TRUE]],

        // ALERTA: aquest ha d'anar abans que el <b perque es barrejan
        '<br( \/)?>' => ['state' => 'br', 'type' => 'br', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "\n\n", 'regex' => TRUE]],


        '<b ?.*?' => ['state' => 'open_bold', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '**', 'regex' => TRUE]],
        '</b>' => ['state' => 'close_bold', 'type' => 'bold', 'action' => 'close'],
        '<i>' => ['state' => 'open_italic', 'type' => 'italic', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '//']],
        '</i>' => ['state' => 'close_italic', 'type' => 'italic', 'action' => 'close'],
        '<u>' => ['state' => 'open_underline', 'type' => 'underline', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '__']],
        '</u>' => ['state' => 'close_underline', 'type' => 'underline', 'action' => 'close'],

        '<pre>\n?<code.*?>(.*?)<\/code>\n?<\/pre>' => ['state' => 'code', 'type' => 'code', 'class' => 'Html2DWCode', 'action' => 'self-contained', 'extra' => ['regex' => TRUE]],

        '<code>(.*?)<\/code>' => ['state' => 'code', 'type' => 'code', 'class' => 'Html2DWMonospace', 'action' => 'self-contained', 'extra' => ['replacement' => "''", 'regex' => TRUE]],

        '<h1' => ['state' => 'open_h1', 'type' => 'h1', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['======', "======"], 'regex' => TRUE]],
        '</h1>' => ['state' => 'close_h1', 'type' => 'h1', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h2' => ['state' => 'open_h2', 'type' => 'h2', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['=====', "====="]]],
        '</h2>' => ['state' => 'close_h2', 'type' => 'h2', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h3' => ['state' => 'open_h3', 'type' => 'h3', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['====', "===="]]],
        '</h3>' => ['state' => 'close_h3', 'type' => 'h3', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h4' => ['state' => 'open_h4', 'type' => 'h4', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['===', "==="]]],
        '</h4>' => ['state' => 'close_h4', 'type' => 'h4', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h5' => ['state' => 'open_h5', 'type' => 'h5', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['==', "=="]]],
        '</h5>' => ['state' => 'close_h5', 'type' => 'h5', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<h6' => ['state' => 'open_h6', 'type' => 'h6', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['==', "=="]]],
        '</h6>' => ['state' => 'close_h6', 'type' => 'h6', 'class' => 'Html2DWMarkup', 'action' => 'close'],
        '<hr' => ['state' => 'hr', 'type' => 'hr', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => "----"]],

        '&nbsp;' => ['state' => 'hr', 'type' => 'hr', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => " "]],



        '<li>' => ['state' => 'list-item', 'type' => 'li', 'class' => 'Html2DWListItem', 'action' => 'open', 'extra' => ['replacement' => "", 'regex' => TRUE]],
        "</li>" => ['state' => 'list-item', 'type' => 'li', 'action' => 'close'],


        '<ul>' => ['state' => 'list', 'type' => 'ul', 'class' => 'Html2DWList', 'action' => 'open', 'extra' => ['container' => 'ul', 'regex' => TRUE]],
        '</ul>' => ['state' => 'list', 'type' => 'ul', 'action' => 'close'],

        '<ol>' => ['state' => 'list', 'type' => 'ol', 'class' => 'Html2DWList', 'action' => 'open', 'extra' => ['container' => 'ol', 'regex' => TRUE]],
        '</ol>' => ['state' => 'list', 'type' => 'ol', 'action' => 'close'],



        '<img' => ['state' => 'image', 'type' => 'image', 'class' => 'Html2DWImage', 'action' => 'self-contained', 'extra' => ['replacement' => ['{{', '}}']]],

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

}