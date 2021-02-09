<?php
require_once "DW2HtmlParser.php";

class DW2HtmlReadonly extends DW2HtmlMarkup
{

    protected function getReplacement($position)
    {
//        if ($this->currentToken['action'] != 'open') {
        if ($position != 'open') {
            return parent::getReplacement($position);
        }

        $tag = parent::getReplacement($position);
//        $base = substr($tag, 0, strlen($tag) - 1);

        // Afegim la referència si escau
        $refId = intval(WiocclParser::$structureStack[count(WiocclParser::$structureStack) - 1]);


//        if ($refId > 0) {
//            $ref = ' data-wioccl-ref="' . $refId . '"';
//        } else {
//            $ref = '';
//        }


        if ($refId > 0) {
            $tag = sprintf($tag, $refId);
//            $ref = ' data-wioccl-ref="' . $refId . '"';
        }
//        else {
//            $ref = '';
//        }

        return $tag;
//        return $base . $ref . '>';
    }
}