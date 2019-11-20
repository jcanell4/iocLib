<?php
require_once "DW2HtmlParser.php";

class DW2HtmlRow extends DW2HtmlInstruction {


    public function open() {
        $return = '';


        // Afegim l'item

//        var_dump($this->currentToken['raw']);
//        preg_split( "/ (@|vs) /", $input );
//
//        die("stop!");

        $class = static::$parserClass;


        $chunks = preg_split("/[\|\^]/", $this->getRawValue());

        $isInnerPrevious = $class::isInner();
        $class::setInner(true);

        $value = '';
        // ignorem el primer i l'últim fragment
        // TODO: Determinar la estructura de la fila

        $row = [];

        for ($i = 1; $i < count($chunks) - 1; $i++) {

            // TODO: determinar el colspan

            $chunk = $chunks[$i];
            $pattern = '/[\^\|](?:' . preg_quote($chunk) . ')[\^\|]/';

            preg_match($pattern, $this->currentToken['raw'], $matches);

            $cell = [];
            if (substr($matches[0], -1, 1) == '^') {
                $cell['tag'] = 'th';
//                $value .= '<th>' . $class::getValue($chunks[$i]) . '</th>';
            } else {
                $cell['tag'] = 'td';
//                $value .= '<td>' . $class::getValue($chunks[$i]) . '</td>';
            }

            $empty = strlen($chunks[$i]) == 0;
            if ($empty && count($row) > 0) {

                // Cerquem el primer chunk que no sigui buit
                for ($j = count($row) - 1; $j >= 0; $j--) {
                    if (strlen($row[$j]['content']) > 0 || $j == 0) {
                        $row[$j]['colspan'] = $row[$j]['colspan'] ? $row[$j]['colspan'] + 1 : 2;
                        break;
                    }
                }

                // es tracta de la primera columna, no ho posem a l'anterior
            } else if ($empty && count($row) == 0) {
                $cell['colspan'] = 1;
            }

            $cell['content'] = $class::getValue($chunks[$i]);


            $row[] = $cell;

        }

        for ($i = 0; $i < count($row); $i++) {

            if (strlen($row[$i]['content']) == 0) {

                if ($i > 0) {
                    // cel·la fusionada amb l'anterior
                    continue;
                } else {
                    // si es la primera cel·la hem de desplaçar el colspan fins a la primera no buida que trobem
                    $found = false;
                    for ($j = 1; $j< count($row); $j++) {

                        if (strlen($row[$j]['content'])>0) {
                            $row[$j]['colspan'] = $row[$j]['colspan'] ? $row[$j]['colspan'] + $j : $j+1;
                            $found = true;
                            break;
                        }
                    }

                    if ($found) {
                        continue;
                    }

                }

                // si es la primera cel·la però son totes fusionades llavors s'ha d'afegir normalment

            }

            $value .= '<' . $row[$i]['tag'];
            if ($row[$i]['colspan']) {
                $value .= ' colspan="' . $row[$i]['colspan'] . '"';
            }

            $value .= '>' . $row[$i]['content'] . '</' . $row[$i]['tag'] . '>';
        }


        $class::setInner($isInnerPrevious);

        $return .= "<tr>" . $value . "<tr>\n";


        return $return;
    }


    public function isClosing($token) {
        // Les files es tanquen només quan es troba el final de la línia
        return $token['value'] == "\n";
    }

}