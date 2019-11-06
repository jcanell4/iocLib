<?php
require_once "DW2HtmlParser.php";

class DW2HtmlHeader extends DW2HtmlInstruction {

    protected $level = 0;

    public function open() {
        $this->level = strlen(trim($this->currentToken['raw']));
        return '<h'.(7-$this->level) . '>';
    }


    public function close() {
        $this->level = strlen(trim($this->currentToken['raw']));
        return '</h'.(7-$this->level) . ">\n";
    }


    public function isClosing($token) {

//        if ($token['action'] == 'close') {
//            var_dump($token);
//            var_dump($this->currentToken);
//            die("trobat token de tancament pel header");
//        }

        $tokenLevel = strlen(trim($token['raw']));


        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
            || ($token['action'] === 'close' && $token['state'] == $this->currentToken['state']
                && $this->level = $tokenLevel)) {


            if (isset($token['extra']) && $token['extra']['block'] && ($token['action'] == 'open')) {
                echo "el nou token és un block\n";
            } else if ($token['action'] === 'close' && $token['state'] == $this->currentToken['state']
                && $this->level = $tokenLevel) {
                echo "el nou token és el token de tancament del top\n";
            }


            return true;
        }
        // Aquests blocs sempre es tanquen quan es troba quelcom, per exemple un salt de línia

    }
}