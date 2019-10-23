<?php
require_once "Html2DWParser.php";

class Html2DWMarkup extends Html2DWInstruction {

    protected function getContent($token) {

        return $this->getReplacement(self::OPEN) . $token['value'];
    }

    protected function resolveOnClose($field) {
        $r = $this->getReplacement(self::CLOSE);

        return $field . $r;
    }
}