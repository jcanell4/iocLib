<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocParser.php';

class Html2DWParser extends IocParser {

    protected static $removeTokenPatterns = [
//        "/\n/"
    ];

    protected static $tokenPatterns = [
        '<p>' => [
            'state' => 'open_p',
        ],

        "\n<\/p>" => [
            'state' => 'close_p',
        ],
        '<\/p>' => [
            'state' => 'close_p',
        ],


        '<b>' => [
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
        /*        "<pre>\n?<code.*?>" => [
        //            'state' => 'open_code',
        //        ],
        /*        "<pre>\n?<code.*?>" => [ // TODO: Això fa que peti però no enten perqué, per altra banda ha de ser self-contained
        //            'state' => 'open_code',
        //        ],
        //        "</code>\n?</pre>" => [
        //            'state' => 'close_code',
        //        ],

        //        "<pre></pre>" => [ // TODO: Això fa que peti però no enten perqué, per altra banda ha de ser self-contained
        /*/
        '<pre>\n?<code.*?>(.*?)<\/code>\n?<\/pre>' => [
//        '<pre>(.*?)<\/pre>' => [ // TODO: Això fa que peti però no enten perqué, per altra banda ha de ser self-contained
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

        '<a ?.*?>' => [
            'state' => 'open_anchor',
        ],
        '<\/a>' => [
            'state' => 'close_anchor',
        ],

        '<img.*?\/>' => [
            'state' => 'image',
        ],
    ];

    protected static $tokenKey = [
        // aquests són els elements buits, sense cap atribut per processar.
        // ALERTA! El replacement s'ha d'especificar al 'open', si es diferent al open i close es fa servir un array amb els dos elements (0 per open i 1 per close)

        '<a ?(.*)?>' => ['state' => 'link', 'type' => 'a', 'class' => 'Html2DWLink', 'action' => 'open', 'extra' => ['replacement' => ["[[", "]]"], 'regex' => TRUE]],
        '</a>' => ['state' => 'link', 'type' => 'a', 'action' => 'close'],

//        '<div>' => ['state' => 'open_div', 'type' => 'div', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ["", "\n"]]],
//        '</div>' => ['state' => 'close_div', 'type' => 'div', 'action' => 'close'],
        '<p>' => ['state' => 'open_p', 'type' => 'paragraph', 'class' => 'Html2DWParagraph', 'action' => 'open', 'extra' => ['replacement' => ["", "\n"]]], // si posem un salt de línia a l'apertura s'afegeix un salt de línia quan es fa un tancament --> es tanca després de **negreta** i després de //cursiva//
        "\n?<\/p>" => ['state' => 'close_p', 'type' => 'paragraph', 'action' => 'close', 'extra' => ['regex' => TRUE]],
        '<b>' => ['state' => 'open_bold', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '**']],
        '</b>' => ['state' => 'close_bold', 'type' => 'bold', 'action' => 'close'],
        '<i>' => ['state' => 'open_italic', 'type' => 'italic', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '//']],
        '</i>' => ['state' => 'close_italic', 'type' => 'italic', 'action' => 'close'],
        '<u>' => ['state' => 'open_underline', 'type' => 'underline', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => '__']],
        '</u>' => ['state' => 'close_underline', 'type' => 'underline', 'action' => 'close'],


        /*        '<pre>\n?<code .*?>' => ['state' => 'open_code', 'type' => 'code', 'class' => 'Html2DWMarkup', 'action' => 'open', 'extra' => ['replacement' => ['<code>', '</code>'], 'regex' => TRUE]],*/
//        '<\code>\n</pre>' => ['state' => 'close_code', 'type' => 'code', 'action' => 'close'],


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
        '<br' => ['state' => 'br', 'type' => 'br', 'class' => 'Html2DWBlockReplacement', 'action' => 'self-contained', 'extra' => ['replacement' => ""]], // ALERTA: a continuació de les marques de salt de línia que fica l'editor hi ha un \n, no cal afegir-lo


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