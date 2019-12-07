<?php
require_once "Html2DWParser.php";

class Html2DWParagraph extends Html2DWMarkup{



//    public function getTokensValue($tokens, &$tokenIndex) {
//
//        var_dump($this->currentToken);
//        die('getTokensValue');
//
//        $content =parent::getTokensValue($tokens, $tokenIndex);
//        var_dump($content, $this->nextToken);
//
//        if (substr($content, -1, 1)== "\n") {
//            $content = substr($content, 0, strlen($content)-1);
//
//        }
//
//        return $this->getReplacement(self::OPEN) . $content;
//    }

//    Això no es crida
    protected function getContent($token) {
        var_dump($token);
        die('getContent');
        return $token['value'];
    }
}