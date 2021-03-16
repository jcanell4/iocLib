<?php
require_once "DW2HtmlParser.php";

class DW2HtmlListItem extends DW2HtmlInstruction {


    protected $solved = false;

    public function open() {
        $return = '';


        // Afegim l'item

        $class = static::$parserClass;

        $isInnerPrevious = $class::isInner();

        $class::setInner(true);

        $value = $class::getValue($this->getRawValue());

        $this->solved = true;

        $class::setInner($isInnerPrevious);

        $return .= $this->getReplacement(self::OPEN) . $value;


        return $return;
    }


    public function isClosing($token) {

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

        // PROBLEMA: això no és possible perquè es tancan els foreach

        if (($this->solved && ($token['state']==='content' || $wiocclClose))
            || (isset($token['extra']) && $token['extra']['block'] === TRUE)) {

//            die ("això no es crida mai");

            // Excepció, el següent és un block però el nivell es superior, en aquest cas s'ha de retornar fals, perquè no es tanca

//            var_dump($token);
            $nextTokenLevel = $this->getLevel($token['raw']);
//            var_dump($nextTokenLevel, $this->extra['level'] );
//            die;


            if ($nextTokenLevel > $this->extra['level']) {
//              var_dump ($token, $nextTokenLevel, $this->extra['level']);
//              die ('el nivell és major');

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