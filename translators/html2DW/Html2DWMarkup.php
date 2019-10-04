<?php
require_once "Html2DWParser.php";

class Html2DWMarkup extends Html2DWInstruction {

    const OPEN = 0;
    const CLOSE = 1;

    protected function getReplacement($position) {

        return is_array($this->extra['replacement']) ? $this->extra['replacement'][$position] : $this->extra['replacement'];
    }

    protected function getContent($token) {
        return $this->getReplacement(self::OPEN) . $token['value'];
    }

    protected function resolveOnClose($field) {
        return $field . $this->getReplacement(self::CLOSE);
    }
}