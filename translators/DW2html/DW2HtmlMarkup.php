<?php
require_once "DW2HtmlParser.php";

class DW2HtmlMarkup extends Html2DWInstruction {



    protected function getContent($token) {
        $value = $token['value'];
        return $this->getReplacement(self::OPEN) . $value;
    }

    protected function resolveOnClose($field) {
        return $field . $this->getReplacement(self::CLOSE);
    }
}