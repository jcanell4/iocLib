<?php
require_once "Html2DWParser.php";

class Html2DWBlock extends Html2DWMarkup{

    public function getTokensValue($tokens, &$tokenIndex) {
        return $this->getReplacement(self::OPEN) . parent::getTokensValue($tokens, $tokenIndex);
    }

    protected function getContent($token) {
        return $token['value'];
    }
}