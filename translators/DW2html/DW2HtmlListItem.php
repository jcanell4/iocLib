<?php
require_once "DW2HtmlParser.php";

class DW2HtmlListItem extends DW2HtmlInstruction {


    protected $solved = false;
    protected $closing = false;

    public function open() {
        $return = '';


        // Primer de tot cal parsejar els [ref] que poden anar davant de la fila:
//        $value = $this->getRawValue();
        // el raw del currentToken és diferent al del getRaw


        $separator = $this->extra['container'] == 'ol'? '-' : '*';

        // ALERTA! Això s'ha d'extreure al list, aquí ha d'arribar ja sense els refs
        $raw = $this->currentToken['raw'];
        $i = strpos($raw, "  " . $separator);
        $refs = substr($raw, 0, $i);
//
//
        $parsedRefs = $this->parseContent($refs);





        $listItem = strstr($raw, "  " . $separator);

        $pos = strpos($listItem, "  " . $separator);
        if ($pos !== false) {
            $listItem = substr_replace($listItem, "", $pos, strlen("  *"));
        }
//        $listItem = str_replace("  *", "", $listItem);
        $listItem = ltrim($listItem);


        // Afegim l'item

        $class = static::$parserClass;

        $isInnerPrevious = $class::isInner();

        $class::setInner(true);

        $value = $class::getValue($listItem);
//        $value = $class::getValue($this->getRawValue());

        $this->solved = true;

        $class::setInner($isInnerPrevious);

        $return .= $this->getReplacement(self::OPEN) . $value;

        if (substr($this->currentToken['raw'],-1) === "\n") {
            $this->closing = true;
        }

        return $parsedRefs. $return;
    }


    public function isClosing($token) {

//        $t = substr($this->currentToken['raw'],-1);
//
//        if ($token['state']==='content' && substr($this->currentToken['raw'],-1) === "\n") {
//            return true;
//        }

        if ($this->closing) {
            return true;
        }

        $wiocclClose = false;
        // ALERTA! només el open provoca el salt
        if ($this->solved && $token['type'] === 'wioccl' && $token['state'] === 'ref-open') {
            // TODO: Cal comprovar si el tipus de node provoca el salt?
            // ALERTA! El problemàtic és el cas en que ja s'ha tancat el LI
            // si el state es content funciona correctament, però si es un ref que hem de fer?
            // no es pot comprovar només el solved perquè llavors  es tanca cada <ul> darrera cada <li>

            preg_match($token['pattern'], $token['raw'], $match);
            $ref = $match[1];

            // ALERTA! Només afegim a la pila els elements que no siguin de tipus content
            $structure = WiocclParser::getStructure();
            if ($structure[$ref]->type === 'readonly') {
                // Només hi ha aquests casos que puguin tancar la llista (per ara només considerem readonly):
                // ':###'
                // '###:'
                // '{@@'
                // '@@}'
                // '{##'
                // '##}'
                // '{#_'
                // '_#}'
                // '{%%'
                // '%%}'
                $wiocclClose = true;
            }
        }

        // Prova: saltar sempre que es trobi un wioccl a continuació? això trencaria un ul que dintre tingués 2 wioccl
        // consecutius, per exemple per crear

        if (($this->solved && ($token['state']==='content' || $wiocclClose))
            || (isset($token['extra']) && $token['extra']['block'] === TRUE)) {

            // Excepció, el següent és un block però el nivell es superior, en aquest cas s'ha de retornar fals, perquè no es tanca
            $nextTokenLevel = $this->getLevel($token['raw']);


            if ($nextTokenLevel > $this->extra['level']) {

                return false;
            }

            return true;
        }

        return false;


    }


    protected function getValue($raw) {
        preg_match($this->currentToken['pattern'], $raw, $match);
        return $match[1];
    }

    protected function getLevel($raw) {
        preg_match("/^( *)/", $raw, $spaces);
        return strlen($spaces[1]) / 2;
    }


}