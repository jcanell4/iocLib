<?php
require_once "DW2HtmlParser.php";

class DW2HtmlListItem extends DW2HtmlInstruction {


    public function open() {
        $return = '';


        // Afegim l'item

        $class = static::$parserClass;

        $isInnerPrevious = $class::isInner();

        $class::setInner(true);

        $value = $class::getValue($this->getRawValue());

        $class::setInner($isInnerPrevious);

//        var_dump($this->getReplacement(self::OPEN));
//        die();

//        $this->pushState($this->currentToken); // Això es fa al Switch del Instruction

        $return .= $this->getReplacement(self::OPEN) . ' ***** '.$value . '+++++';


        return $return;
    }



    public function isClosing($token) {

        if (isset($token['extra']) && $token['extra']['block'] === TRUE) {

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