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
            case 'accounting':
                return $this->getValueTable($token, $id, $type);

            case 'figure':
                return $this->getValueFigure($token, $id);

            case 'text':
            case 'example':
            case 'note':
            case 'reference':
            case 'important':
            case 'quote':
                return $this->getValueText($token, $type);


        }


    }

    protected function getValueText($token, $type) {
        $fields = $this->getFields($token);

        $large = FALSE;

        if (isset($fields['large'])) {
            $type = 'textl';
        }

        $html = '<div class="ioc' . $type . '" data-dw-box-text="' . $type . '"' . ($large ? $large : '') . '>'
            . '<div class="ioccontent">';

        if (isset($fields['title'])) {
            $html .= '<p class="ioctitle" data-dw-field="title" data-ioc-optional>' . $fields['title'] . '</p>';
        }


        $content = $this->getContent($token);

        if (substr($content, -2, 2) !== "\n\n") {
            if (substr($content, -1, 1) == "\n") {
                $content .= "\n";
            } else {
                $content .= "\n\n";
            }
        }

        $content = $this->parseContent($content);

        $content = str_replace('<p>', '<p class="editable-text">', $content);


//        $html .= '<p class="editable-text">' . $content . '</p>'
        $html .= $content . '</div></div>';


        return $html;
    }


    protected function getValueTable($token, $id, $type) {


        $fields = $this->getFields($token);


        //$pre = $this->getPreContent($fields, $id, $type);
        $pre = $this->getPreContent($fields, $id, 'table', $type);
        $content = $this->getContent($token);

        $value = $this->parseTable($content);

        $post = "</div>";

        return $pre . $value . $post;
    }

    protected function getValueFigure($token, $id) {

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
                $fields[$matches[1][$i]] = trim($matches[2][$i]);

            }
        }

        return $fields;
    }

    protected function getContent($token) {
        $typeContent = "/(?:^::.*?:.*?\n)(?:^  :.*?:.*?\n)*(.*):::$/ms";
        if (preg_match($typeContent, $token['raw'], $matches)) {

            $content = $matches[1];
        } else {
            $content = "Error: contingut no reconegut";
        }

        return $content;
    }

    protected function getPreContent($fields, $id, $type, $realType = false) {
        if (!$realType) {
            $realType = $type;
        }

        // fem el parse dels id perquè poden haver etiquetes de referència

        $previousParsingState = $this->parsingContent;
        $this->parsingContent = true;

        $pre = '<div class="ioc' . $type . ' ' . $fields['type'] . '" data-dw-box="' . $realType . '" data-dw-type="'
            . $fields['type'] . "\">\n";
        $pre .= '<div class="iocinfo">';
        $pre .= '<a data-dw-link="' . $realType . '" name="' . $id . '">';
        $pre .= '<b contenteditable="false" data-dw-field="id">ID:</b> ' . $this->parseContent($id) . "<br>\n";
        $pre .= '</a>';

        if (isset($fields['title'])) {
            $pre .= '<b contenteditable="false" data-dw-field="title">Títol:</b> ' . $this->parseContent($fields['title']) . "<br>\n";
        }

        if (isset($fields['footer'])) {
            $pre .= '<b contenteditable="false" data-dw-field="footer">Peu:</b> ' . $this->parseContent($fields['footer']) . "<br>\n";
        }

        $pre .= '</div>';

        $this->parsingContent = $previousParsingState;

        return $pre;
    }

    protected function parseTable($content) {

        // Dividim el contingut en files
        preg_match_all('/^(.*?)$/ms', $content, $matchesRow);

        $rows = $matchesRow[1];

        $table = [];

        $this->parsingContent = true;

        // Problema amb els ref=
        //  - Per contingut: els caràcters que delimiten la taula com ^ i | es troben envoltats per [ref] i [/ref],
        //      solucionat eliminant tots els refs de tipus content.
        //  - Per files:
        //      L'apertura es troba al principi de la línia però el tancament es troba al principi de la següent



        //$teststructure = WiocclParser::getStructure();

        $rowAttrs = [];

        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            // ALERTA! les notes incluen un enllaç a la signatura per tant s'inclou un | que es interpretat com
            // una columna. Per aquest motiu fem aquí una substitució del | de la signatura per & i ho restaurem després


            // ALERTA! Cal gestionar les referéncies manualment, això és una excepció i no agafa la informació del parser
            // PROBLEMA: no es pot ficar una fila o una cel·la dins dins d'un div, ni span, ni cap element que no
            //      sigui de taula (TR, TD o TH), cal ficar la informació de la referència com atribut del TR

            // Reorganització dels ref de fila, només es pot donar si al principi hi ha un ref i no és la última línia


            $patternOpen = "/^(?:\[\/ref=\d+\])*\[ref=(.*?)\]/ms";
            if ($rowIndex < count($rows) - 1 && preg_match($patternOpen, $rows[$rowIndex], $match)) {

                $refId = $match[1];

                // S'ha de fer una comprovació similar a l'anterior però cercant el tancament
                $patternClose = "/^(?:\[\/ref=\d+\])*\[\/ref=" . $refId . "\]/ms";


                // ALERTA! Un foreach pot inclorue múltiples files per iteració, cerquem el tancament en totes les línies posteriors
                // ALERTA! si no es troba el tancament no cal fer res, no és un foreach

                $closingIndex = -1;
                for ($i = $rowIndex + 1; $i < count($rows); $i++) {
                    $foundClose = preg_match($patternClose, $rows[$i], $matchClose);

                    // ALERTA! Problema detectat! quan hi ha més d'un element al foreach el tancament s'ha de fer a
                    // la última i no a la primera! així doncs s'han de recorrer tots els elements de la taula
                    if ($foundClose) {
                        $closingIndex = $i;
//                        break;
                    }
                }

                if ($closingIndex !== -1) {
                    $refOpen = '[ref=' . $refId . ']';
                    $refClose = '[/ref=' . $refId . ']';

                    $rows[$rowIndex] = str_replace($refOpen, '', $rows[$rowIndex]);
                    $rows[$closingIndex] = str_replace($refClose, '', $rows[$closingIndex]);
//                    $rowAttrs[$rowIndex]['data-wioccl-ref'] = $refId;


                    // La fila on s'ha trobat el tancament no s'inclou
                    for ($i = $rowIndex; $i<$closingIndex; $i++) {

                        // Canvi de sistema, desem només la última referència
//                        if (!isset($rowAttrs[$i]['data-wioccl-ref'])) {
//                            $rowAttrs[$i]['data-wioccl-ref'] = [];
//                        } else {
//                            // ALERTA! ja existeix, això sempre ha de contenir només 1 referència (la idea anterior era fer servir una llista de ids separada per comes però ho vam canviar)
//                            $test = false;
//                        }
//                        $rowAttrs[$i]['data-wioccl-ref'][] = $refId;

                        $rowAttrs[$i]['data-wioccl-ref'] = $refId;
                    }
                }

            }


            $rows[$rowIndex] = preg_replace('/\[\[(.*?)\|(.*?)\]\]/ms', '[[$1&$2]]', $rows[$rowIndex]);


            // dividim les files en cel.les
            $cols = preg_split("/[\|\^]/", $rows[$rowIndex]);


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


                            if ((strlen($table[$colIndex][$j]['content']) > 0 && trim($table[$colIndex][$j]['content']) != ":::")
                                || $j == 0) {
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

                // Restaurem el separador de la signatura |
                $cols[$colIndex] = preg_replace('/\[\[(.*?)&(.*?)\]\]/ms', '[[$1|$2]]', $cols[$colIndex]);


                // Cal fer un parser per cel·las encara que en alguns casos ja s'haurà parsejat per resoldre els ref de fila
                $cell['content'] = $this->parseContent($cols[$colIndex]);

//                $class::setInner($isInnerPrevious);


                $table[$colIndex][$rowIndex] = $cell;

            }


        }

        $this->parsingContent = false;


        return $this->makeTable($table, $rowAttrs);
    }


    protected function makeTable($tableData, $rowAttrs) {

        $table = '<table data-dw-cols="' . count($tableData[0]) . '">';


        $len = $this->findRowCount($tableData);

        for ($rowIndex = 0; $rowIndex <= $len; $rowIndex++) {

            $attrs = '';

            if (isset($rowAttrs[$rowIndex])) {
                foreach ($rowAttrs[$rowIndex] as $key => $value) {
                    if (is_array($value)) {
                        $attrs .= ' ' . $key . '="' . implode(',', $value) . '"';
                    } else {
                        $attrs .= ' ' . $key . '="' . $value . '"';
                    }

                }
            }
            $table .= '<tr' . $attrs . '>';


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
