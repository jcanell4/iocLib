<?php
require_once "Html2DWParser.php";

class Html2DWMarkup extends Html2DWInstruction {

//    protected function getContent($token) {
//
//        // Això no es crida
//        return $this->getReplacement(self::OPEN) . $token['value'];
//    }

    protected function resolveOnClose($field) {

        if (isset($this->extra['trim']) && $this->extra['trim']) {
            $field = trim($field);
        }

        return $this->getReplacement(self::OPEN) . $field . $this->getReplacement(self::CLOSE);
    }
}