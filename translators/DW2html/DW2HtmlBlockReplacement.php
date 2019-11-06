<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBlockReplacement extends DW2HtmlInstruction {

    protected $value;


    public function isClosing($token) {
        // Aquests blocs sempre es tanquen quan es troba quelcom, per exemple un salt de línia
        return true;
    }



}