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
        for ($i = 1; $i < count($chunks) - 1; $i++) {

            // TODO: Determinar si es td o th
            $chunk = $chunks[$i];
            $pattern = '/[\^\|](?:' . preg_quote($chunk) . ')[\^\|]/';

            preg_match($pattern, $this->currentToken['raw'], $matches);

            if (substr($matches[0], -1, 1) == '^') {
                $value .= '<th>' . $class::getValue($chunks[$i]) . '</th>';
            } else {
                $value .= '<td>' . $class::getValue($chunks[$i]) . '</td>';
            }

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