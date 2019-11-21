<?php
require_once "Html2DWParser.php";

class Html2DWTable extends Html2DWMarkup {


    protected function getContent($token) {
        var_dump($token);

        // extraiem el contingut
        preg_match($token['pattern'], $token['raw'],$matches);
        $content = $matches[1];



        // extraiem les files
        $rowPattern = '/<tr>(.*?)<\/tr>/ms';
        preg_match_all($rowPattern, $token['raw'],$rowMatches);
        var_dump($rowMatches);
        $rows = $rowMatches[0];

        // recorrem les files extraiem les cel·les
        for ($rowIndex = 0; $rowIndex<count($rows); $rowIndex++) {
            $cellPattern = '/<(?:td|th)>(.*?)<\/(?:td|th)>/ms';
            preg_match_all($cellPattern, $rows[$rowIndex],$colMatches);

            var_dump($colMatches);


        }





        return 'TODO: ficar la taula parsejada';




//        die("Html2DWTable#getContent");
    }

    public function getTokensValue($tokens, &$tokenIndex) {

        die("Html2DWTable#getTokensValue");

//        $token = $tokens[$tokenIndex-1];
//
//        $count = count(static::$stack);
//        $index = $count - 1;
//
//        static::$stack[$index]['list'] = $token['extra']['container'];
//
//
//        // El top és aquest mateix UL, hem d'agafar l'anterior (-2)
//        if (count(static::$stack) > 1) {
//
//            // Cas 1: aquésta llista no es filla d'un item
//            $previous = static::$stack[$count - 2];
//
//            // Cas 2: aquésta llista està imbricada
//            if ($previous['state'] == 'list-item') {
//                $previous = static::$stack[$count - 3];
//            }
//
//            if (isset($previous['list'])) {
//                static::$stack[$index]['level'] = ++$previous['level'];
//            } else {
//                static::$stack[$index]['level'] = 1;
//            }
//        } else {
//            // Si és el primer element de l'stack llavors es nivell 1
//            static::$stack[$index]['level'] = 1;
//        }
//
//        $pre = $this->getReplacement(self::OPEN);
//
////         Si el previ es un list-item a la apertura s'ha d'afegir un salt de línia i no s'ha d'afegir en tancar el list-item
//        if ($this->getPreviousState()['state'] == 'list-item') {
//            $pre = "\n" . $pre;
//
//            static::$stack[$count - 2]['skip-close'] = true;
//        }
//
////        return parent::getTokensValue($tokens, $tokenIndex);
//        return $pre . parent::getTokensValue($tokens, $tokenIndex);

    }

}