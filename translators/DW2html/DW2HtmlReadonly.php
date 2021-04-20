<?php
require_once "DW2HtmlParser.php";

class DW2HtmlReadonly extends DW2HtmlMarkup
{

    protected function getReplacement($position)
    {
        $tag = parent::getReplacement($position);

//        if ($this->currentToken['action'] != 'open') {
//            return parent::getReplacement($position);
//        }
//
//        $tag = parent::getReplacement($position);
//        $base = substr($tag, 0, strlen($tag) - 1);
//
//        // Afegim la referència si escau
//        $refId = WiocclParser::$structureStack[count(WiocclParser::$structureStack) - 1];
//
//
//        if ($refId > 0) {
//            $ref = ' data-wioccl-ref="' . $refId . '"';
//        } else {
//            $ref = '';
//        }

        // TODO: valorar si afegir el open/close com extra

        if ($position === self::OPEN) {
            if ($this->currentToken['state'] === 'readonly-open') {
                WiocclParser::$readonlyStack++;
            } else {
                WiocclParser::$readonlyStack--;
            }
        }


        return $tag;
    }

}