<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBlockReplacement extends Html2DWInstruction {

    protected function getContent($token) {
        return $this->extra['replacement'];
    }
}