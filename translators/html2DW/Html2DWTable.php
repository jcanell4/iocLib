<?php
require_once "Html2DWParser.php";

class Html2DWTable extends Html2DWMarkup {


    protected function getContent($token) {
//        die("Html2DWTable#getContent");

        ++static::$instancesCounter;


//        $maxCols = $this->extractVarName($token['raw'],'data-dw-cols', false);

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


            for ($i = 0; $i < $cellNumber; $i++) {
                $cell = [];


                // ALERTA! La posició no correspón a la posició a la taula, s'ha de desplaçar pels row spans
                //      Si tromem un rowspan marquem les posicions necessaries
                //      Si es troba ja ocupada la cel·la en desplacem cap a la dreta

//                var_dump($colMatches);
                // extreure tipus TD o TH
                if (substr($colMatches[0][$i], -5, 5) == '</td>') {
                    $cell['tag'] = 'td';
                } else {
                    $cell['tag'] = 'th';
                }

                // extreure l'alineació
                $alignPattern = '/class="(.*?)align.*?"/';
                if (preg_match($alignPattern, $colMatches[0][$i], $match)) {
                    // afegim files buides a la dreta fins colspan-1
                    $cell['align'] = $match[1];
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


                // extreure colspan
                $colspanPattern = '/colspan="(.*?)"/';
                if (preg_match($colspanPattern, $colMatches[0][$i], $match)) {
                    // afegim files buides a la dreta fins colspan-1

                    $colspan = $match[1];

                    for ($j = 1; $j < $colspan; $j++) {
                        ++$colIndex;
                        $table[$colIndex][$rowIndex] = ['tag' => $cell['tag'], 'content' => ''];
                    }
                }

                // extreure rowspan
                $colspanPattern = '/rowspan="(.*?)"/';
                if (preg_match($colspanPattern, $colMatches[0][$i], $match)) {
                    // afegim files amb ::: cap a sota fins a rowspan-1
                    $rowspan = $match[1];

                    for ($j = 1; $j < $rowspan; $j++) {
                        $table[$colIndex][$rowIndex + $j] = ['tag' => $cell['tag'], 'content' => ' ::: '];
                    }
                }


                $colIndex++;

            }


        }


        --static::$instancesCounter;

//        var_dump($table);

        return $this->makeTable($table);

//        return 'TODO: ficar la taula parsejada';


//        die("Html2DWTable#getContent");
    }

    protected function makeTable($tableData) {

        $content = '';
        // TODO: Alerta! falta la caixa, això només afegeix la taula
        for ($row = 0; $row < count($tableData[0]); $row++) {

            for ($col = 0; $col < count($tableData); $col++) {
                // només s'afegeix la marca de la esquerra a la primera columna
                if ($col === 0) {
                    if ($tableData[$col][$row]['tag'] === 'th') {
                        $content .= '^';
                    } else {
                        $content .= '|';
                    }
                }


                $value = $tableData[$col][$row]['content'];


                $align = $tableData[$col][$row]['align'];
                if (($align === 'center' || $align === 'left') && substr($value, 0, 2) !== '  ') {
                    // s'han d'afegir els caràcters d'alineació
                    $value = '  ' . $value;
                }

                if (($align === 'center' || $align === 'right') && substr($value, -2, 2) !== '  ') {
                    // s'han d'afegir els caràcters d'alineació
                    $value .= '  ';
                }

                $content .= $value;

                if ($tableData[$col][$row]['tag'] === 'th') {
                    $content .= '^';
                } else {
                    $content .= '|';
                }
            }

            $content .= "\n";

        }


//        var_dump($tableData);
//        die('TODO: fer la conversio de les dades a una taula de DW');

        return $content;

    }

    public function getTokensValue($tokens, &$tokenIndex) {

        // això no es crida
        die("Html2DWTable#getTokensValue");

    }

}