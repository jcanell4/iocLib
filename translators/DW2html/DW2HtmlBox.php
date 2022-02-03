<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBox extends DW2HtmlInstruction
{

    protected $parsingContent = false;

    public function open()
    {

        $token = $this->currentToken;


        // Extrerure els camps
        // ^::tipus:ID$
        $typePattern = '/^(?:\[\/?ref=\d*\])*::(.*?):(.*)$/m';
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

            case 'include':
                return $this->getValueInclude($token);

        }


    }

    protected function getValueText($token, $type)
    {
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

        $content = $this->parseContent($content, false);

        $content = str_replace('<p>', '<p class="editable-text">', $content);


//        $html .= '<p class="editable-text">' . $content . '</p>'
        $html .= $content . '</div></div>';


        return $html;
    }


    protected function getValueTable($token, $id, $type)
    {


        $fields = $this->getFields($token);


        //$pre = $this->getPreContent($fields, $id, $type);
        $pre = $this->getPreContent($fields, $id, 'table', $type);
        $content = $this->getContent($token);

        $value = $this->parseTable($content);

        $post = "</div>";

        return $pre . $value . $post;
    }

    protected function getValueFigure($token, $id)
    {

        $type = 'figure';

        $fields = $this->getFields($token);

        $pre = $this->getPreContent($fields, $id, $type);
        $content = $this->getContent($token);

        // ALERTA[Xavi] eliminem el trailing \n aquí perquè no sempre és aplicable, a les taules
        // s'han de conservar per poder fer el parser correcte de les files

        $post = "</div>";

        if (substr($content, -1) == "\n") {
            $content = substr_replace($content, "", -1);
        }

        $value = $this->parseContent($content);


        return $pre . $value . $post;
    }

    protected function getValueInclude($token)
    {


        // És page o section?
        $matches = null;
        $type = null;
        if (preg_match('/^{{(page|section)>/m', $token['raw'], $matches)) {
            $type = $matches[1];
        }

        $content = null;
        if (preg_match('/^{{.*>(.*?)}}$/m', $token['raw'], $matches)) {
            $content = $matches[1];
        }

        $post = "</div>";

        $pre = "<div class=\"iocinclude\" data-dw-include=\"$content\" data-dw-include-type=\"$type\"" .
            "contenteditable=\"false\" data-dw-highlighted=\"true\">";

        $value = "<span>incloent [$type]: $content</span>";

        return $pre . $value . $post;
    }

    public function isClosing($token)
    {

        return !$this->parsingContent;

    }

    protected function getFields($token)
    {
        $fieldPattern = "/^  :(.*?):(.*)$/m";
        $fields = [];
        if (preg_match_all($fieldPattern, $token['raw'], $matches)) {


            for ($i = 0; $i < count($matches[0]); $i++) {
                $fields[$matches[1][$i]] = trim($matches[2][$i]);

            }
        }

        return $fields;
    }

    protected function getContent($token)
    {
        $typeContent = "/^(?:\[\/?ref=\d*\])*(?:::.*?:.*?\n)(?:  :.*?:.*?\n)*(.*):::$/ms";
        //$typeContent = "/(?:^::.*?:.*?\n)(?:^  :.*?:.*?\n)*(.*):::$/ms";
        if (preg_match($typeContent, $token['raw'], $matches)) {

            $content = $matches[1];
        } else {
            $content = "Error: contingut no reconegut";
        }

        return $content;
    }

    protected function getPreContent($fields, $id, $type, $realType = false)
    {
        if (!$realType) {
            $realType = $type;
        }

        // fem el parse dels id perquè poden haver etiquetes de referència

        $previousParsingState = $this->parsingContent;
        $this->parsingContent = true;


        $refId = WiocclParser::$structureStack[count(WiocclParser::$structureStack) - 1];
//        $result .= '<span data-wioccl-ref="'. $refId.'">'. $item->getContent($currentToken) . '</span>';


        $pre = '<div ';

        if ($refId > 0) {
            $pre .= 'data-wioccl-ref="' . $refId . '" ';

        }

        $pre .= 'class="ioc' . $type . ' ' . $fields['type'] . '" data-dw-box="' . $realType . '" data-dw-type="'
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

    protected function parseTable($content)
    {

        // Dividim el contingut en files
        preg_match_all('/^(.*?[\|\^])]?$/ms', $content, $matchesRow);
//        preg_match_all('/^(.*?)$/ms', $content, $matchesRow);

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

//        $mainRefId = -1;

        // ALERTA! el ^ es clau perquè volem ignorar el tancament de ref que pertany a la línia anterior
        $patternOpen = "/^(?:\[\/ref=\d+\])*\[ref=(.*?)\]/ms";

        // Cal estreure les files que només son wioccl
        // ALERTA! hi ha algun cas en que el tancament de la caixa ::: va seguida del wioccl
//        $pureRefPattern = "/^(\[\/ref=\d+\]+\[ref=.*?\])[|\^\n]/ms";

        $pureRefPattern = "/^(\[\/ref=\d+\]+\[ref=.*?\])$/ms";

        $newRows = [];

        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            if (preg_match($pureRefPattern, $rows[$rowIndex], $match)) {
                // Afegim una fila de referències pures
                $newRows[] = $match[1];
            } else {
                // No cal modificar la fila original perquè totes les referències anteriores a | o ^ són descartades
                $newRows[] = $rows[$rowIndex];
            }


        }

        // Reassignem per no modificar la resta del codi
        $rows = $newRows;

        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            // ALERTA! les notes incluen un enllaç a la signatura per tant s'inclou un | que es interpretat com
            // una columna. Per aquest motiu fem aquí una substitució del | de la signatura per & i ho restaurem després


            // ALERTA! Cal gestionar les referéncies manualment, això és una excepció i no agafa la informació del parser
            // PROBLEMA: no es pot ficar una fila o una cel·la dins dins d'un div, ni span, ni cap element que no
            //      sigui de taula (TR, TD o TH), cal ficar la informació de la referència com atribut del TR

            // Reorganització dels ref de fila, només es pot donar si al principi hi ha un ref i no és la última línia


            if ($rowIndex < count($rows) && preg_match($patternOpen, $rows[$rowIndex], $match)) {

                $refId = $match[1];
//                $mainRefId = $refId;

                // S'ha de fer una comprovació similar a l'anterior però cercant el tancament
                //$patternClose = "/^(?:\[\/ref=\d+\])*\[\/ref=" . $refId . "\]/ms";
                $patternClose = "/\[\/ref=" . $refId . "\]/ms";


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

                // ALERTA! EXCEPCIÓ: pot ser  un foreach-buit que es troba com a últim element d'una taula

                if ($rowIndex == count($rows) - 1) {
                    $foundClose = preg_match($patternClose, $rows[$rowIndex], $matchClose);
                    if ($foundClose) {
                        $closingIndex = $i;
                    }
                }


                if ($closingIndex !== -1) {
                    $refOpen = '[ref=' . $refId . ']';
                    $refClose = '[/ref=' . $refId . ']';

                    $rows[$rowIndex] = str_replace($refOpen, '', $rows[$rowIndex]);
                    $rows[$closingIndex] = str_replace($refClose, '', $rows[$closingIndex]);
//                    $rowAttrs[$rowIndex]['data-wioccl-ref'] = $refId;


                    // La fila on s'ha trobat el tancament no s'inclou
                    for ($i = $rowIndex; $i < $closingIndex; $i++) {

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

            if (count($cols) == 0) {
                continue;
            }

            $tagPattern = '/(\^|\|)/ms';
            if (preg_match_all($tagPattern, $rows[$rowIndex], $tagMatches)) {


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
            } else {
                // Hi havia una fila però no hi havia res, comprovem si hi ha $refId, si es troba s'ha de ficar
                // una fila buida
                // ALERTA[Xavi] es gestiona control·lant la existencia de l'atribut al makeTable()
            }

        }

        $this->parsingContent = false;

//         El mainRefId només s'utilitza quan el nombre de files és 0. Si no s'ha trobat res
//        if ($mainRefId === -1 && preg_match($patternOpen, $content, $match)) {
//            // Cerquem una marca d'apertura, pel cas en que hi hagi algun wioccl però sense dades
//            $mainRefId = $match[1];
//        }

//        return $this->makeTable($table, $rowAttrs, $mainRefId);
        return $this->makeTable($table, $rowAttrs);
    }


    protected function makeTable($tableData, $rowAttrs)
    {

        $table = '<table data-dw-cols="' . count($tableData[0]) . '">';


        $len = $this->findRowCount($tableData, $rowAttrs);

        // Aquest cas es dona quan no s'ha trobat cap fila però hi ha un ref. Cal ficar la referència
        // TODO: això no s'ha de fer aquí:
        //  1 s'ha d'afegir si no hi ha fila corresponent a l'atribut
        //  2 s'ha de fer el bucle fins al len, no només pels continguts de la fila

//
//        if ($len === 0 && count($rowAttrs) >0) {
//
//            foreach ($rowAttrs as $rowIndex => $row) {
//                foreach ($row as $key => $value) {
//                    $table .= '<tr ' . $key . '="' . $value . '"></tr>';
//                }
//            }
//        }


        // ALERTA:
        //      - el valor de $cell no s'utilitza, només requerim el $rowIndex
        //      - suposem que totes les files tenen el mateix nombre de cel·les
        //      - el ref de totes les files és el mateix, això no és important perquè es reconstrueixen a partir del pare


        for ($rowIndex = 0; $rowIndex < $len; $rowIndex++) {

            // TODO: comprovar si és correcte en tots els casos
            if (!isset($tableData[0][$rowIndex])) {
                foreach ($rowAttrs as $row) {
                    foreach ($row as $key => $value) {
                        $table .= '<tr ' . $key . '="' . $value . '"></tr>';
                    }
                }
                continue;
            }


            //            $cell = $tableData[$rowIndex];

            //        foreach ($tableData[0] as $rowIndex => $cell ) {
            //for ($rowIndex = 0; $rowIndex <= $len; $rowIndex++) {


//            if ($tableData[0][$rowIndex]) {
//                continue;
//            }

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

    protected function findRowCount($tableData, $rowAttrs)
    {
        // El nombre d'elements a cada fila no sempre correspon al nombre de files ja que pot haver cel·les amb rowspan

        $rows = 0;

        foreach ($tableData as $col) {
            // Posem el cursor de l'array a la última posició
            end($col);

            // ALERTA! les claus comencen en 0, cal sumar 1 o es descarta quan només hi ha 1 fila
            if (key($col) + 1 > $rows) {
                $rows = key($col) + 1;
            }
        }

        foreach ($rowAttrs as $key => $value) {
            // Posem el cursor de l'array a la última posició
            if ($key + 1 > $rows) {
                $rows = $key + 1;
            }
        }


        return $rows;
    }
}

?>
