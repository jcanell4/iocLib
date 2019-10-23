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

            // Cas 1: aquésta llista no es filla d'un item
            $previous = static::$stack[$count - 2];

            // Cas 2: aquésta llista està imbricada
            if ($previous['state'] == 'list-item') {
                $previous = static::$stack[$count - 3];
            }

            if (isset($previous['list'])) {
                static::$stack[$index]['level'] = ++$previous['level'];
            } else {
                static::$stack[$index]['level'] = 1;
            }
        } else {
            static::$stack[$index]['level'] = 1;
        }

        return $this->getReplacement(self::OPEN) . parent::getTokensValue($tokens, $tokenIndex);

    }

    protected function getContent($token) {
        return '';
    }
}