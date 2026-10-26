<?php
require_once "DW2HtmlParser.php";

class DW2HtmlMarkup extends DW2HtmlInstruction
{


//    public function open() {
//        die('open');
//    }

    public function isClosing($token)
    {

//        if ($token['action'] == 'close') {
//            var_dump($token);
//            var_dump($this->currentToken);
//            die("trobat token de tancament pel header");
//        }


        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
            || ($token['action'] === 'close' && $token['state'] == $this->currentToken['state']
                && $token['type'] == $this->currentToken['type'])) {


//            if (isset($token['extra']) && $token['extra']['block'] && ($token['action'] == 'open')) {
//                echo "el nou token és un block\n";
//            } else if ($token['action'] === 'close' && $token['state'] == $this->currentToken['state']
//                && $token['type'] == $this->currentToken['type']) {
//                echo "el nou token és el token de tancament del top\n";
//            }


            return true;
        } else {
            return false;
        }
        // Aquests blocs sempre es tanquen quan es troba quelcom, per exemple un salt de línia

    }


    protected function getReplacement($position)
    {
        if ($this->currentToken['action'] != 'open') {
            return parent::getReplacement($position);
        }

        $tag = parent::getReplacement($position);
        $base = substr($tag, 0, strlen($tag) - 1);

        // Afegim la referència si escau
        $refId = WiocclParser::$structureStack[count(WiocclParser::$structureStack) - 1];


        if ($refId > 0) {
            $ref = ' data-wioccl-ref="' . $refId . '"';
        } else {
            $ref = '';
        }


        return $base . $ref . '>';
    }
}