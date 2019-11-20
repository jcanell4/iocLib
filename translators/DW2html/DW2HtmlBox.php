<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBox extends DW2HtmlInstruction {

    protected $parsingContent = false;

    public function open() {

        $token = $this->currentToken;


        // Extrerure els camps
        // ^::tipus:ID$
        $typePattern = "/^::(.*?):(.*)$/m";
        $type = "unknown";
        $id = "none";

        if (preg_match($typePattern, $token['raw'], $matches)) {
//            var_dump($matches);

            $type = $matches[1];
            $id = $matches[2];
        }


        // ^  :field:value$
        $fieldPattern = "/^  :(.*?):(.*)$/m";
        $fields = [];
        if (preg_match_all($fieldPattern, $token['raw'], $matches)) {


            for ($i = 0; $i < count($matches[0]); $i++) {
//                echo $i . " " . $matches[$i][0] . " : " . $matches[$i][1];
                $fields[$matches[1][$i]] = $matches[2][$i];

            }

//            var_dump($fields);
        }

        $typeContent = "/(?:^::.*?:.*?\n)(?:^  :.*?:.*?\n)*(.*)^:::$/ms";
        if (preg_match($typeContent, $token['raw'], $matches)) {

            $content = $matches[1];
//            var_dump($content);
        } else {
            $content = "Error: contingut no reconegut";
        }


        $value = $this->parseTable($content);

        $pre = '<div class="ioc' . $type . ' ' . $fields['type'] . "\" data-dw-box=\"table\">\n";
        $pre .= '<div class="iocinfo">';
//        $pre .= '<a name="' . $id . '"><strong>ID:</strong> ' . $id . "<br></a>\n";
        $pre .= '<strong>ID:</strong> ' . $id . "<br>\n";

        if (isset($fields['title'])) {
            $pre .= '<strong>Títol:</strong> ' . $fields['title'] . "<br>\n";
        }
        $pre .= '</div>';

        $post = '</div>';




        echo $pre. $value . $post . "\n";

        return $pre. $value . $post;
    }

    public function isClosing($token) {

        return !$this->parsingContent;

    }

    protected function parseTable($content) {

        // Dividim el contingut en files
        preg_match_all('/^(.*?)$/ms', $content, $matchesRow);

        $rows = $matchesRow[1];

        $table = [];

        $this->parsingContent = true;

        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            // dividim les files en cel.les
            $cols = preg_split("/[\|\^]/", $rows[$rowIndex]);

            // Eliminem el primer i l'últim elements perque sempre son buits
            array_pop($cols);
            array_shift($cols);



            for ($colIndex = 0; $colIndex < count($cols); $colIndex++) {
                $cell = [];

                // cerquem el fragment incloent els separadors de columnes per determinar si és capçalera
                $pattern = '/[\^\|](?:' . preg_quote($cols[$colIndex]) . ')[\^\|]/';
                preg_match($pattern, $this->currentToken['raw'], $matches);

                if (substr($matches[0], -1, 1) == '^') {
                    $cell['tag'] = 'th';
                } else {
                    $cell['tag'] = 'td';
                }

                // gestionem el colspan
                $empty = strlen($cols[$colIndex]) == 0;
                if ($empty && $colIndex > 0) {

                    // Cerquem el primer chunk que no sigui buit
                    for ($j = $colIndex - 1; $j >= 0; $j--) {
                        if (strlen($table[$j][$rowIndex]['content']) > 0 || $j == 0) {
                            $table[$j][$rowIndex]['colspan'] = $table[$j][$rowIndex]['colspan'] ? $table[$j][$rowIndex]['colspan'] + 1 : 2;
                            break;
                        }
                    }

                    // es tracta de la primera columna, no ho posem a l'anterior
                } else if ($empty && $colIndex == 0) {
                    $cell['colspan'] = 1;
                }

                // Gestionem el rowspan
                if (trim($cols[$colIndex]) == ":::") {


                    if ($rowIndex == 0) {
                        $table[$colIndex][$rowIndex]['rowspan'] = 1;
                    } else {
                        // Recorrem tots els elements cap amunt
                        for ($j = $rowIndex - 1; $j >= 0; $j--) {
                            if (trim($table[$colIndex][$j]['content']) != ":::" || $j == 0) {
                                $table[$colIndex][$j]['rowspan'] = $table[$colIndex][$j]['rowspan'] ? $table[$colIndex][$j]['rowspan'] + 1 : 2;
                                break;
                            }
                        }
                    }

                    continue;
                }

                $class = static::$parserClass;
                $isInnerPrevious = $class::isInner();
                $class::setInner(true);

                $cell['content'] = $class::getValue($cols[$colIndex]);

                $class::setInner($isInnerPrevious);

                $table[$colIndex][$rowIndex] = $cell;
            }


        }

        $this->parsingContent = false;


        return $this->makeTable($table);
    }

    protected function makeTable($tableData) {
        $table = '<table>';


        for ($rowIndex = 0; $rowIndex < count($tableData[0]); $rowIndex++) {
            $table .= '<tr>';

            for ($colIndex = 0; $colIndex < count($tableData); $colIndex++) {


                // la primera columna es fila, es una fusió cap a la esquerra
                if ($colIndex==0 && count($tableData) > 1
                    && strlen($tableData[$colIndex][$rowIndex]['content'] == 0)
                    && isset($tableData[$colIndex][$rowIndex]['colspan'])) {
                    // desplacem el colspan cap a la dreta

                    if (isset($tableData[$colIndex+1][$rowIndex]['colspan'])) {
                        $tableData[$colIndex+1][$rowIndex]['colspan'] += $tableData[$colIndex][$rowIndex]['colspan'];
                    } else {
                        $tableData[$colIndex+1][$rowIndex]['colspan'] = 2;
                    }

                    continue;
                }

                // es una cel.la fusionada per fila
                if ($colIndex>0 && strlen($tableData[$colIndex][$rowIndex]['content']) == 0) {
                    continue;
                }

                else if (trim($tableData[$colIndex][$rowIndex]['content']) == ":::"
                    && !$tableData[$colIndex][$rowIndex]['colspan']
                    && !$tableData[$colIndex][$rowIndex]['rowspan']) {
                    // es una cel·la fusionada per columna
                    continue;
                } else if (trim($tableData[$colIndex][$rowIndex]['content']) == ":::"
                    && ($tableData[$colIndex][$rowIndex]['colspan']
                        || !$tableData[$colIndex][$rowIndex]['rowspan'])) {
                    // Aquest cas es pot donar si la primera cel·la de la columna fos fusionada
                    $tableData[$colIndex][$rowIndex]['content'] = '';

                }

                $table .= '<' . $tableData[$colIndex][$rowIndex]['tag'];

                if (isset($tableData[$colIndex][$rowIndex]['colspan'])) {
                    $table .= ' colspan="' . $tableData[$colIndex][$rowIndex]['colspan'] . '"';
                }

                if (isset($tableData[$colIndex][$rowIndex]['rowspan'])) {
                    $table .= ' rowspan="' . $tableData[$colIndex][$rowIndex]['rowspan'] . '"';
                }

                $table .= '>';

                $table .= $tableData[$colIndex][$rowIndex]['content'];

                $table .= '</' . $tableData[$colIndex][$rowIndex]['tag'] . '>';

            }


            $table .= '</tr>';
        }

        $table .= '</table>';

        return $table;
    }

}