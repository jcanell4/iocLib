<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBox extends DW2HtmlInstruction {


    public function open() {

        $token = $this->currentToken;
//        $ns = FALSE;
//        $url = $this->extractUrl($token, $ns);
//
//        $anchorPattern = "/#(.*?)[|\]]/";
//        $textPattern = "/\|(.*?)[|\]]/";
//
//        $anchor = FALSE;
//
//        // Aquest és opcional
//        if (preg_match($anchorPattern, $token['raw'], $matchAnchor)) {
//            $anchor = $matchAnchor[1];
//        }
//
//        if (preg_match($textPattern, $token['raw'], $matchText)) {
//            $text = $matchText[1];
//        } else {
//            $text = $url;
//        }

        // TODO: fer servir aquest informació per crear la capça

        // Extrerure els camps
        // ^::tipus:ID$
        $typePattern = "/^::(.*?):(.*)$/m";
        if (preg_match($typePattern, $token['raw'], $matches)) {
//            var_dump($matches);

            $type = $matches[1];
            $id = $matches[2];
        }


        // ^  :field:value$
        $fieldPattern = "/^  :(.*?):(.*)$/m";
        if (preg_match_all($fieldPattern, $token['raw'], $matches)) {
            $fields = [];

            for ($i = 1; $i<count($matches); $i++) {
//                echo $i . " " . $matches[$i][0] . " : " . $matches[$i][1];
                $fields[$matches[$i][0]] = $matches[$i][1];

            }

//            var_dump($fields);
        }

        $typeContent = "/(?:^::.*?:.*?\n)(?:^  :.*?:.*?\n)*(.*)^:::$/ms";
        if (preg_match($typeContent, $token['raw'], $matches)) {

            $content= $matches[1];
//            var_dump($content);
        } else {
            $content = "Error: contingut no reconegut";
        }


        // BOX: eliminenm els \n?::: finals i tenim que el conten va desde ??? fins al final del que quedi



        // TODO: amb les dades es construeix la capsa

        // Es parseja el contingut que va dins
        $class = static::$parserClass;

        $isInnerPrevious = $class::isInner();

        $class::setInner(true);



        // TODO: aquest contingut no s'està parsejant correctament, no se perquè no reconeix el patró!

        // De totes maneres caldria instanciar aquí el tipus taula i pasar el content perquè s'ha de
        // tenir en compte que necesitem coneixer la estructura de la taula abans de fer la conversió
        // a HTML (col spans y row spans), fins que no es troba l'ultim colspan / rowspan no es sap
        // la quantitat de columnes



        $value = $class::getValue($content);

        $class::setInner($isInnerPrevious);



        return "\n#####\n" . $value . "\n#####\n";
    }

    public function isClosing($token) {


        return true;

    }

}