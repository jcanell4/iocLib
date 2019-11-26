<?php
require_once "Html2DWParser.php";

class Html2DWTable extends Html2DWMarkup {


    protected function getContent($token) {
//        die("Html2DWTable#getContent");

        ++static::$instancesCounter;


        $maxCols = $this->extractVarName($token['raw'],'data-dw-cols', false);

        // extraiem el contingut
        preg_match($token['pattern'], $token['raw'], $matches);
//        $content = $matches[1];


        // extraiem les files
        $rowPattern = '/<tr>(.*?)<\/tr>/ms';
        preg_match_all($rowPattern, $token['raw'], $rowMatches);
//        var_dump($rowMatches);
        $rows = $rowMatches[0];



        $table = [];

        // recorrem les files extraiem les cel·les
        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            $cellPattern = '/<(?:td|th).*?>(.*?)<\/(?:td|th)>/ms';
            preg_match_all($cellPattern, $rows[$rowIndex], $colMatches);


            $colIndex = 0;
            $cellNumber = count($colMatches[1]);

            echo $cellNumber . "\n";

//            var_dump($colMatches);
            // Cas: es una fila completa fusionada
//            if ($cellNumber === 1) {
//                die();
//                for ($i = 0; $i < $maxCols; $i++) {
//
//                }
//
//            }

            for ($i = 0; $i < $cellNumber; $i++) {

                // ALERTA! La posició no correspón a la posició a la taula, s'ha de desplaçar pels row spans
                //      Si tromem un rowspan marquem les posicions necessaries
                //      Si es troba ja ocupada la cel·la en desplacem cap a la dreta

                var_dump($colMatches);
                // extreure tipus TD o TH
                if (substr($colMatches[0][$i], -5, 5) == '</td>') {
                    $cell['tag'] = 'td';
                } else {
                    $cell['tag'] = 'th';
                }


//
                $class = static::$parserClass;
                $isInnerPrevious = $class::isInner();
                $class::setInner(true);

                $cell['content'] = $class::getValue($colMatches[1][$i]);

                $class::setInner($isInnerPrevious);


                // Això ho fem per avançar el cursor sobre les cel·les que ja s'han establer per un rowspan a una fila anterior
                while (isset($table[$colIndex][$rowIndex])) {
                    $colIndex++;
                }

                $table[$colIndex][$rowIndex] = $cell;


                // extreure rowspan
                $colspanPattern = '/rowspan="(.*?)"/';
                if (preg_match($colspanPattern, $colMatches[0][$i], $match)) {
                    // afegim files amb ::: cap a sota fins a rowspan-1
                    $rowspan = $match[1];

                    for ($j = 1; $j < $rowspan; $j++) {
                        $table[$colIndex][$rowIndex + $j] = ['tag' => $cell['tag'], 'content' => ' ::: '];
                    }
                }

                // extreure colspan
                $colspanPattern = '/colspan="(.*?)"/';
                if (preg_match($colspanPattern, $colMatches[0][$i], $match)) {
                    // afegim files buides a la dreta fins colspan-1

                    $colspan = $match[1];

                    for ($j = 1; $j < $colspan; $j++) {
                        ++$colIndex;
                        $table[$colIndex][$rowIndex] = ['tag' => $cell['tag'], 'content' => ''];
//                        $table[$colIndex+$j+1][$rowIndex] = ['tag' => $cell['tag'], 'content' => ''];
                    }
                }


                $colIndex++;

            }


        }


        --static::$instancesCounter;

        var_dump($table);

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