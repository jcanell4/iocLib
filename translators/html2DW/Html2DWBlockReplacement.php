<?php
require_once "Html2DWParser.php";

class Html2DWBlockReplacement extends Html2DWInstruction {

    protected function getContent($token) {
        return $this->extra['replacement'];
    }
}