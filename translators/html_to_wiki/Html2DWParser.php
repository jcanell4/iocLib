<?php
class Html2DWParser extends WiocclParser{
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
    ];

    protected static  $tokenKey = [
        '<b>' => ['state' => 'open_bold', 'type' => 'bold', 'class' => 'Html2DWMarkup', 'action' => 'open'],
        '</b>' => ['state' => 'close_bold', 'type' => 'bold', 'action' => 'close'],

    ];

    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables=NULL)
    {

//        $instruction = new WiocclInstruction($text, $arrays, $dataSource, $resetables);
        $instruction = new Html2DWInstruction($text, $arrays, $dataSource, $resetables);

        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens

        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
    }

//    protected static function tokenize($rawText) {
//
//    }

}