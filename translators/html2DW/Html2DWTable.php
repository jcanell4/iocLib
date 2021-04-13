<?php
require_once "Html2DWParser.php";

class Html2DWTable extends Html2DWMarkup {


    protected function getContent($token) {
//        die("Html2DWTable#getContent");

        ++static::$instancesCounter;


//        $maxCols = $this->extractVarName($token['raw'],'data-dw-cols', false);

        $tableHtml = $token['raw'];

        // Eliminem el tag tbody
        $tableHtml = str_replace('<tbody>', '', $tableHtml);
        $tableHtml = str_replace('</tbody>', '', $tableHtml);


        // extraiem el contingut
        preg_match($token['pattern'], $tableHtml, $matches);
//        $content = $matches[1];


        // extraiem les files
        $rowPattern = '/<tr>(.*?)<\/tr>|<span data-wioccl-ref.*?<\/span>/ms';
        preg_match_all($rowPattern, $tableHtml, $rowMatches);
//        var_dump($rowMatches);
        $rows = $rowMatches[0];


        $table = [];

        // recorrem les files extraiem les cel·les
        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {

            if (substr( $rows[$rowIndex], 0, 3 ) == '<tr') {
                // es tracta d'una fila
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

                    $class = static::$parserClass;
                    $isInnerPrevious = $class::isInner();
                    $class::setInner(true);

                    $cell['content'] = $class::getValue($colMatches[1][$i]);

                    $class::setInner($isInnerPrevious);


                    // Això ho fem per avançar el cursor sobre les cel·les que ja s'han establert per un rowspan a una fila anterior
                    while (isset($table[$colIndex][$rowIndex])) {
                        $colIndex++;
                    }


                    $table[$colIndex][$rowIndex] = $cell;


                    $colspan = 0;

                    // extreure colspan
                    $colspanPattern = '/colspan="(.*?)"/';
                    if (preg_match($colspanPattern, $colMatches[0][$i], $match)) {
                        // afegim files buides a la dreta fins colspan-1

                        $colspan = $match[1];

                        $auxColIndex = $colIndex;
                        for ($j = 1; $j < $colspan; $j++) {
                            ++$auxColIndex;
                            $table[$auxColIndex][$rowIndex] = ['tag' => $cell['tag'], 'content' => ''];
                        }
                    }

                    // extreure rowspan
                    $colspanPattern = '/rowspan="(.*?)"/';

                    //  Cal recorrer el colspan pels casos en que hi ha més d'una columna

                    for ($k=0; $k< ($colspan? $colspan : 1); $k++) {

                        if (preg_match($colspanPattern, $colMatches[0][$i], $match)) {
                            // afegim files amb ::: cap a sota fins a rowspan-1
                            $rowspan = $match[1];

                            for ($j = 1; $j < $rowspan; $j++) {
                                $table[$colIndex + $k][$rowIndex + $j] = ['tag' => $cell['tag'], 'content' => ' ::: '];
                            }
                        }
                    }

                    $colIndex++;

                }

            } else {
                // es tracta d'un wioccl
                $wiocclPattern = '/<span data-wioccl-ref.*?>(.*)<\/span>/ms';

                 preg_match($wiocclPattern, $rows[$rowIndex], $wiocclMatches);

                $table[0][$rowIndex] = [
                    "wioccl" => $wiocclMatches[0]
                ];
            }

        }


        --static::$instancesCounter;

        return $this->makeTable($table);
    }

    protected function makeTable($tableData) {

        $content = '';
        $lastCellTag = '';

//        $isMultiline = false;

        // Fem el reemplaç dels \n* per //
        // Si s'ha fet cap canvi llavors la taula es multilínia

        for ($row = 0; $row < count($tableData[0]); $row++) {

            for ($col = 0; $col < count($tableData); $col++) {

                if ($col===0 && $tableData[$col][$row]['wioccl']) {
                    $class = static::$parserClass;
                    $content .= $class::getValue($tableData[$col][$row]['wioccl']);
                    $lastCellTag = "";
                    break;
                }

                if ($tableData[$col][$row]['tag'] === 'th') {
                    $content .= '^';
                } else {
                    $content .= '|';
                }


                $value = $tableData[$col][$row]['content'];

                if (strpos($value, "\n") !== FALSE) {
//                    $isMultiline = true;
                    $value = preg_replace("/\n+/", "\\\\\\ ", $value);
                }

                // ALERTA: fem un trim per la esquerra i la dreta per assegurar que el nombre d'espais és correcte
                // perquè l'editor ACE pot afegir espais adicionals quan formateja la visualització

                $align = $tableData[$col][$row]['align'];
                if ($align === 'center' || $align === 'left') {
                    // s'han d'afegir els caràcters d'alineació
                    $value = '  ' . ltrim($value);
                }

                if ($align === 'center' || $align === 'right') {
                    // s'han d'afegir els caràcters d'alineació

                    // ALERTA[Xavi]: si hi ha un <wioccl::if que s'obre i es tanca al final del $value la alineació
                    // s'ha de ficar abans del wioccl::if
                    $innerWiocclIfPattern = '/(.*?)(<WIOCCL:IF condition=".*?">\^.*<\/WIOCCL:IF>)$/ms';

                    if (preg_match_all ( $innerWiocclIfPattern , $value , $wiocclMatches)) {
                        $value = rtrim($wiocclMatches[1][0]) . '  ' .$wiocclMatches[2][0];
                    } else {
                        $value = rtrim($value) . '  ';
                    }

                }


                $content .= $value;

                // Aquést últim es irellevant, només es te en compte el primer simbol de cad cel·la
                if ($tableData[$col][$row]['tag'] === 'th') {
                    $lastCellTag = "^\n";
                } else {
                    $lastCellTag = "|\n";
                }
            }

            $content .= $lastCellTag;

        }


//        var_dump($tableData);
//        die('TODO: fer la conversio de les dades a una taula de DW');

        // Desactivada la comprovació de multilínia, totes les taules supoerten multilínia perque es fa servir \\ en lloc de \n
//        if ($isMultiline) {
//            $content = "[" . $content . "]\n";
//        }

        return $content;

    }

    public function getTokensValue($tokens, &$tokenIndex) {

        // això no es crida
        die("Html2DWTable#getTokensValue");

    }

}