<?php
require_once "DW2HtmlParser.php";

class DW2HtmlMarkup extends DW2HtmlInstruction {


//    public function open() {
//        die('open');
//    }

    public function isClosing($token) {

//        if ($token['action'] == 'close') {
//            var_dump($token);
//            var_dump($this->currentToken);
//            die("trobat token de tancament pel header");
//        }


        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
            || ($token['action'] === 'close' && $token['state'] == $this->currentToken['state']
                && $token['type'] == $this->currentToken['type'])) {


            if (isset($token['extra']) && $token['extra']['block'] && ($token['action'] == 'open')) {
                echo "el nou token és un block\n";
            } else if ($token['action'] === 'close' && $token['state'] == $this->currentToken['state']
                && $token['type'] == $this->currentToken['type']) {
                echo "el nou token és el token de tancament del top\n";
            }


            return true;
        }
        // Aquests blocs sempre es tanquen quan es troba quelcom, per exemple un salt de línia

    }
}