<?php
require_once "Html2DWParser.php";

class Html2DWWioccl extends Html2DWInstruction {

    protected function getContent($token) {
        preg_match($token['pattern'], $token['raw'], $match);
        $refId = $match[1];


        $testStructure = Html2DWParser::$structure[$refId];
        return Html2DWParser::$structure[$refId]->toWioccl();

//        return $this->extra['replacement'];
    }
}