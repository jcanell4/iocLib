<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBox extends DW2HtmlInstruction {

    protected $parsingContent = false;

    public function open() {

        $token = $this->currentToken;


        // Extrerure els camps
        // ^::tipus:ID$
        $typePattern = '/^::(.*?):(.*)$/m';
        $type = 'unknown';
        $id = 'none';

        if (preg_match($typePattern, $token['raw'], $matches)) {
//            var_dump($matches);

            $type = $matches[1];
            $id = $matches[2];
        }


        switch ($type) {
            case 'table':
                return $this->getValueTable($token, $id);

            case 'figure':
                return $this->getValueFigure($token, $id);
        }


    }


    public function getValueTable($token, $id) {

        $type = 'table';

        $fields = $this->getFields($token);


        $pre = $this->getPreContent($fields, $id, $type);
        $content = $this->getContent($token);

        $value = $this->parseTable($content);

        $post = "</div>";

        return $pre . $value . $post;
    }

    public function getValueFigure($token, $id) {

        $type = 'figure';

        $fields = $this->getFields($token);

        $pre = $this->getPreContent($fields, $id, $type);
        $content = $this->getContent($token);

        $value = $this->parseContent($content);

        $post = "</div>";

        return $pre . $value . $post;
    }

    public function isClosing($token) {

        return !$this->parsingContent;

    }

    protected function getFields($token) {
        $fieldPattern = "/^  :(.*?):(.*)$/m";
        $fields = [];
        if (preg_match_all($fieldPattern, $token['raw'], $matches)) {


            for ($i = 0; $i < count($matches[0]); $i++) {
                $fields[$matches[1][$i]] = $matches[2][$i];

            }
        }

        return $fields;
    }

    protected function getContent($token) {
        $typeContent = "/(?:^::.*?:.*?\n)(?:^  :.*?:.*?\n)*(.*)^:::$/ms";
        if (preg_match($typeContent, $token['raw'], $matches)) {

            $content = $matches[1];
        } else {
            $content = "Error: contingut no reconegut";
        }

        return $content;
    }

    protected function getPreContent($fields, $id, $type) {
        $pre = '<div data-dw-box="' . $type . '" class="ioc' . $type . ' ' . $fields['type'] . '" data-dw-type="'
            . $fields['type'] . "\">\n";
        $pre .= '<div class="iocinfo">';
        $pre .= '<a data-dw-link="' . $type.'" name="' . $id . '">';
        $pre .= '<b contenteditable="false" data-dw-field="id">ID:</b> ' . $id . "<br>\n";
        $pre .= '</a>';

        if (isset($fields['title'])) {
            $pre .= '<b contenteditable="false" data-dw-field="title">Títol:</b> ' . $fields['title'] . "<br>\n";
        }

        if (isset($fields['title'])) {
            $pre .= '<b contenteditable="false" data-dw-field="footer">Peu:</b> ' . $fields['footer'] . "<br>\n";
        }

        $pre .= '</div>';

        return $pre;
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


            $tagPattern = '/(\^|\|)/ms';
            preg_match_all($tagPattern, $rows[$rowIndex], $tagMatches);


            for ($colIndex = 0; $colIndex < count($cols); $colIndex++) {
                $cell = [];


                $firstChar = $tagMatches[0][$colIndex];

                if ($firstChar === '^') {
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

                // Gestionem l'alineació
                $start = substr($cols[$colIndex], 0, 2);
                $end = substr($cols[$colIndex], -2, 2);


                if ($start === "  " && $end === "  ") {
                    $cell['align'] = "center";
                } else if ($start === "  ") {
                    $cell['align'] = "right";
                } else if ($end === "  ") {
                    $cell['align'] = "left";
                }


                // Gestionem el rowspan
                if (trim($cols[$colIndex]) === ":::") {


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

//                $class = static::$parserClass;
//                $isInnerPrevious = $class::isInner();
//                $class::setInner(true);

//                $cell['content'] = $class::getValue($cols[$colIndex]);
                $cell['content'] = $this->parseContent($cols[$colIndex]);

//                $class::setInner($isInnerPrevious);

                $table[$colIndex][$rowIndex] = $cell;
            }

        }

        $this->parsingContent = false;


        return $this->makeTable($table);
    }



    protected function makeTable($tableData) {

        $table = '<table data-dw-cols="' . count($tableData[0]) . '">';


        $len = $this->findRowCount($tableData);

        for ($rowIndex = 0; $rowIndex <= $len; $rowIndex++) {
            $table .= '<tr>';


            for ($colIndex = 0; $colIndex < count($tableData); $colIndex++) {


//                $isMergedCol = trim($tableData[$colIndex][$rowIndex]['content']) == ":::";
                $colSpan = isset($tableData[$colIndex][$rowIndex]['colspan']) ? $tableData[$colIndex][$rowIndex]['colspan'] : false;
                $rowSpan = isset($tableData[$colIndex][$rowIndex]['rowspan']) ? $tableData[$colIndex][$rowIndex]['rowspan'] : false;
                $isEmpty = strlen($tableData[$colIndex][$rowIndex]['content']) == 0;

                if ($colIndex == 0 && count($tableData) > 1 && $isEmpty && $colSpan) {
                    $tableData[$colIndex][$rowIndex]['colspan'] = $colSpan;
                }


                // es una cel.la fusionada per fila
                if ($colIndex > 0 && $isEmpty) {
                    continue;
                } else if ($tableData[$colIndex][$rowIndex] === null) {
                    // es una cel·la fusionada per columna
                    continue;
                }


                $table .= '<' . $tableData[$colIndex][$rowIndex]['tag'];

                if ($colSpan) {
                    $table .= ' colspan="' . $colSpan . '"';
                }

                if ($rowSpan) {
                    $table .= ' rowspan="' . $rowSpan . '"';
                }

                $class = '';


                $align = isset($tableData[$colIndex][$rowIndex]['align']) ? $tableData[$colIndex][$rowIndex]['align'] : false;

                if ($align === 'left') {
                    $class = 'leftalign';
                } else if ($align === 'right') {
                    $class = 'rightalign';
                } else if ($align === 'center') {
                    $class = 'centeralign';
                }


                if (strlen($class) > 0) {
                    $table .= ' class="' . $class . '"';
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

    protected function findRowCount($tableData) {
        // El nombre d'elements a cada fila no sempre correspon al nombre de files ja que pot haver cel·les amb rowspan

        $rows = 0;

        foreach ($tableData as $col) {
            // Posem el cursor de l'array a la última posició
            end($col);

            if (key($col) > $rows) {
                $rows = key($col);
            }
        }

        return $rows;
    }
}

?>
