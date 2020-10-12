<?php
require_once "Html2DWParser.php";

class Html2DWMarkup extends Html2DWInstruction {

//    protected function getContent($token) {
//
//        // Això no es crida
//        return $this->getReplacement(self::OPEN) . $token['value'];
//    }

    protected function resolveOnClose($result) {

        if (isset($this->extra['trim']) && $this->extra['trim']) {
            $result = trim($result);
        }

        return $this->getReplacement(self::OPEN) . $result . $this->getReplacement(self::CLOSE);
    }
}