<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBlockReplacement extends Html2DWInstruction {

    protected function getContent($token) {
//        echo '>>' . $token['value'] . "<<";
        return $this->extra['replacement'];
    }
}