<?php
require_once "DW2HtmlParser.php";

class DW2HtmlIgnored extends DW2HtmlInstruction {


    public function open() {
        $token = $this->currentToken;
        return $token['raw'];
    }

    // Aquest element s'autotanca
    public function isClosing($token) {
        return false;
    }
}