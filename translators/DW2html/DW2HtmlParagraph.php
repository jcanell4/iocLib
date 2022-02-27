<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlParagraph extends DW2HtmlInstruction {


    public function getReplacement($position)
    {

        // ALERTA! els paràgrafs buits també han d'anar referenciats, però hi ha alguns casos en que això provoca
        // algun salt adicional


       // return parent::getReplacement($position);

        $class = static::$parserClass;
//
//        // El primer element del structure stack és el root NOMÉS quan no és inner!, en aquest no cal afegir la referència
        if ($position == self::OPEN && !($class::isInner() || (!$class::isInner() && count(WiocclParser::$structureStack) > 1))) {
            return is_array($this->extra['replacement']) ? $this->extra['replacement'][$position] : $this->extra['replacement'];
        }

        //return parent::getReplacement($position);

        // EXCEPCIÓ: Si l'element corresponent és de tipus field, no afegim la refèrencia al paràgraf, només
        // s'ha d'eliminar el content que ja estarà marcat en un spawn amb el wioccl
        $refId = $this->getRefId();
        $top = WiocclParser::getStructure()[$refId];

        $tag = is_array($this->extra['replacement']) ? $this->extra['replacement'][$position] : $this->extra['replacement'];

        if ($position === self::OPEN && $top->type !=='field') {
            $this->addRefId($tag);
        }

        return $tag;
    }

    public function isClosing($token) {
        // un doble salt de línia sempre tanca un paràgraf

        // Afegit el salt de línia simple
        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
            || $token['raw'] === "\n\n" || $token['raw'] === "\n") {

            return true;
        } else {

            return false;
        }

    }
}
