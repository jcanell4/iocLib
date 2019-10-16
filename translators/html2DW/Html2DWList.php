<?php
require_once "Html2DWParser.php";

class Html2DWList extends Html2DWMarkup {


    public function getTokensValue($tokens, &$tokenIndex) {
        $token = $tokens[$tokenIndex-1];
        $count = count(static::$stack);
        $index = $count - 1;

        static::$stack[$index]['list'] = $token['extra']['container'];

        // El top és aquest mateix UL, hem d'agafar l'anterior (-2)
        if (count(static::$stack) > 1) {
            $previous = static::$stack[$count - 2];


            if (isset($previous['list'])) {
                static::$stack[$index]['level'] = ++$previous['level'];
            } else {
                static::$stack[$index]['level'] = 1;
            }
        } else {
            static::$stack[$index]['level'] = 1;
        }

//        var_dump($tokens);
//        echo "#######";
//        die();

        return parent::getTokensValue($tokens, $tokenIndex);
    }

}