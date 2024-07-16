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
//        $typePattern = '/^(?:\[\/?ref=\d*\])*::(.*?):(.*)$/m';
        $typePattern = '/^(?P<prebox>(\[\/?ref=\d*\])*)::(?P<type>.*?):(?P<id>.*)$/m';
        $type = 'unknown';
        $id = 'none';

        if (preg_match($typePattern, $token['raw'], $matches)) {
//            var_dump($matches);
            $prebox = $matches['prebox'];
            $type = $matches['type'];
            $id = $matches['id'];
        }

        if ($prebox) {
            $preboxContent = $this->parseContent($prebox, false);
        } else {
            $preboxContent = "";
        }


        switch ($type) {
            case 'table':
            case 'accounting':
                $result = $this->getValueTable($token, $id, $type);
                break;

            case 'figure':
                $result = $this->getValueFigure($token, $id);
                break;

            case 'text':
            case 'example':
            case 'note':
            case 'reference':
            case 'copytoclipboard':
            case 'important':
            case 'quote':
                $result = $this->getValueText($token, $type);
                break;

            case 'include':
                $result = $this->getValueInclude($token);
                break;
        }

        return $preboxContent . $result;
    }

    protected function getValueText($token, $type)
    {
        $fields = $this->getFields($token);

        $large = FALSE;

        if (isset($fields['large'])) {
            $type = 'textl';
            unset($fields['large']);
        }

        $html = '<div class="ioc' . $type . '" data-dw-box-text="' . $type . '"' . ($large ? $large : '') . '>'
            . '<div class="ioccontent">';

//        foreach($fields as $field=>$value) {
//            $html .= '<p data-dw-field="' .$field .'" data-ioc-optional><b class="no-save" contenteditable="false">'. $field . ':</b> ' . $value . '</p>';
//        }

        // Solució temporal: només permetem editar el títol, actualment no es pot
        // afegir cap etiqueta per indicar quin és l'atribut que s'està editant
        // en principi l'únic atribut addicional sembla ser "offset"
        foreach ($fields as $field => $value) {
            $html .= '<p class="ioctitle" data-dw-field="' . $field . '" data-ioc-optional'
                . ($field !== 'title' ? ' contenteditable="false"' : '')
                . '>' . $value . '</p>';
        }


//        if (isset($fields['title'])) {
//            $html .= '<p class="ioctitle" data-dw-field="title" data-ioc-optional>' . $fields['title'] . '</p>';
//        }
//
//        if (isset($fields['offset'])) {
//            $html .= '<p class="ioctitle" data-dw-field="offset" data-ioc-optional contenteditable="false">' . $fields['offset'] . '</p>';
//        }

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


        $post = "</div>";

        // ALERTA[Xavi] eliminem qualsevol possible espai o salt de línia, a la figura només
        // hi ha d'haver la figura
//        if (substr($content, -1) == "\n") {
//            $content = substr_replace($content, "", -1);
//        }

        $content = trim($content);

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

        // ALERTA[Xavi] hi han camps sense dos punts, a més ja no pot haver refs davant de la taula
//        $typeContent = "/^(?:\[\/?ref=\d*\])*(?:::.*?:.*?\n)(?:  :.*?:.*?\n)*(.*):::$/ms";
//        $contentPattern = "/(?:^::.*?:.*?\n)|(?:^  :.*?:?.*?\n)*|^(.*?):::$/ms";
        $contentPattern = "/(?:^::.*?:.*?\n)(?:^  :.*?:?.*?\n)*^(?P<content>.*?):::$/ms";

        // TODO: això no funciona correctament, el preg_match només retorna el primer non-capture group, cosa que no hauria de fer
        // si fem servir el preg_match_all semcla que cal agafar el darrer grup capturat es igual si és tracta del primer o el darrer)
        if (preg_match_all($contentPattern, $token['raw'], $matches)) {
            $content = $matches['content'][0];
//            $content = $matches[1];
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

        $replacements = ["title" => "Títol", "footer" => "Peu", "width" => "Amplada de columna"];

        foreach ($fields as $key => $field) {
            $label = in_array($key, $replacements) ? $replacements[$key] : $key;
            $pre .= '<b contenteditable="false" data-dw-field="' . $key . '">' . $label . ':</b> ' . $this->parseContent($field) . "<br>\n";
        }

        $pre .= '</div>';

        $this->parsingContent = $previousParsingState;

        return $pre;
    }

    protected function parseTable($content)
    {

        // Eliminem el marcador de multilínia:
        //  cas 1: hi ha preref: [[ref
        // cas 2: comença la taula [^ o [|
        if (strlen($content) > 2) {
            $check = substr($content, 0, 2);
            if ($check === "[[" || $check === "[^" || $check == "[|") {
                $content = (substr($content, 1));
            }
        }


        // Dividim el contingut en files
        preg_match_all('/^([\|\[\^].*?[\|\]\^])$/ms', $content, $matchesRow);

        $test = WiocclParser::getStructure();

        // PROBLEMA: com que ara els [/ref] corresponents a tancaments wioccl acabats amb \n afegeixen
        // el salt de línia, ara ens trobem línies que són només un seguit de refs.

        // Idea: només es considera un \n legitim si la fila conté com a mínim un | o un ^
        // això continuarà fallant si ens trobem un wioccl amb un salt de línia dintre d'una taula



        $rows = [];
        $preRefs = [];
        $postRefs = [];

        $temp = "";
        $index = 0;
        for ($i = 0; $i < count($matchesRow[1]); $i++) {
            // LA fila pot contenir prefrefs i postrefs
            $raw = $matchesRow[1][$i];


            if (strpos($raw, "|") === false && strpos($raw, "^") === false) {
                // No és una fila, conté només refs

                if ($i<count($matchesRow[1])-1) {
                    // Cas 1: hi han més files
                    $temp .= $raw;
                    continue;

                } else {
                    // Cas 2: no hi ha més files, això diria que no ha de passar, però si passa
                    // no es descartarà res
                    $raw = $temp . $raw;
                    $temp = '';
                }


            } else {
                // Si hi ha $temp l'afegim
                $raw = $temp . $raw;
                $temp = '';
            }


            $len = strlen($raw);

            $startPos = 0;
            $endPos = 0;
            if (preg_match_all("/\||\^/ms", $raw, $posMatches, PREG_OFFSET_CAPTURE)) {
                $startPos = $posMatches[0][0][1];
                // TODO: a quin index correspon el darrer element trobat?? assignar al $end

                $endPos = $posMatches[0][count($posMatches[0]) - 1][1];


                if ($startPos > 0) {
                    $preRefs[$index] = substr($raw, 0, $startPos);
                }

                if ($endPos < $len - 1) {
                    // TODO: capturar el contingut des de $endpos fins al final i afegir-la al postrefs
                    $endLen = $len - $endPos - 1;
                    $postRefs[$index] = substr($raw, $endPos + 1, $endLen + 1);
                } else {
                    $endLen = 0;
                }

                // TODO: retallem el contingut

                $cropLen = $len - $startPos - $endLen;
                $raw = substr($raw, $startPos, $cropLen);

            } else {
                // ALERTA[Xavi]això ara s'ignora perquè els refs els fiquem tots a la primera fila amb contingut
                //
                // és una fila sense files, han de ser tot referències open/close
                $preRefs[$index] = $raw;
                $raw = '';
            }
            $rows[$index] = $raw;
            $index++;
        }

        $table = [];

        $this->parsingContent = true;


        // PROBLEMES AMB ELS REFS: S'han de processar els que hi han al principi i al final de cada fila
        //  tots els refs que hi hagin des-del principi de la fila fins al primer | o ^
        //  tots els refs que hi hagin des-del darrer | o ^ fins al final de la fila <-- compte amb les files amb
        //      multi línia! els refs s'han de trobar com |[ref=000][ref=...]$

        //$teststructure = WiocclParser::getStructure();

        $rowAttrs = [];

        $preRows = [];
        $postRows = [];


        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {

            $preRow = '';
            $postRow = '';


            $rows[$rowIndex] = preg_replace('/\[\[(.*?)\|(.*?)\]\]/ms', '[[$1&$2]]', $rows[$rowIndex]);

            // dividim les files en cel.les
            $cols = preg_split("/[\|\^]/", $rows[$rowIndex]);

            // Fem el parse del prefref si hi ha
            if (isset($preRefs[$rowIndex])) {
                // el retorn serà spans, però ha de ser un tr amb totes les cel·les

                $preRow = $this->parseContent($preRefs[$rowIndex], false);
                $preRow = '<tr class="discardable"><td colspan="' . (count($cols) - 2) . '">' . $preRow . '</td></tr>';
            }

            // ALERTA! això aquí s'ha de fer després del parse del preref!
            $refId = WiocclParser::$structureStack[count(WiocclParser::$structureStack) - 1];
            if ($refId > 0) {
                $rowAttrs[$rowIndex]['data-wioccl-ref'] = $refId;
            }


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

                    // Restaurem el separador de la signatura |
                    $cols[$colIndex] = preg_replace('/\[\[(.*?)&(.*?)\]\]/ms', '[[$1|$2]]', $cols[$colIndex]);

                    // Cal fer un parser per cel·las encara que en alguns casos ja s'haurà parsejat per resoldre els ref de fila
                    $cell['content'] = $this->parseContent($cols[$colIndex]);


                    $table[$colIndex][$rowIndex] = $cell;

                }
            } else {
                // una fila buida
                // ALERTA[Xavi] es gestiona control·lant la existencia de l'atribut al makeTable()
            }

            // Processem els postrefs per actualitzar els tancaments
            if (isset($postRefs[$rowIndex])) {
                // només es processen per actualitzar l'stack, no cal retornar res
                $postRow = $this->parseContent($postRefs[$rowIndex], false);
                $postRow = '<tr class="discardable"><td colspan="' . (count($cols) - 2) . '">' . $postRow . '</td></tr>';
            }

            $preRows[$rowIndex] = $preRow;
            $postRows[$rowIndex] = $postRow;
            // TODO: Com s'afegeix el postrow i prerow  a la taula??
        }

        $this->parsingContent = false;

        return $this->makeTable($table, $rowAttrs, $preRows, $postRows);
    }


    protected function makeTable($tableData, $rowAttrs, $preRows, $postRows)
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

            $table .= $preRows[$rowIndex];

            // TODO: comprovar si és correcte en tots els casos
            // ALERTA[Xavi] això és necessari pels merge de cel·les
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

            $table .= $postRows[$rowIndex];
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
