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

        // El primer element del structure stack és el root NOMÉS quan no és inner!, en aquest no cal afegir la referència
        if ($position == self::OPEN && ($class::isInner() || (!$class::isInner() && count(WiocclParser::$structureStack) > 1))) {

            $refId = WiocclParser::$structureStack[count(WiocclParser::$structureStack)-1];
            $tag = parent::getReplacement($position);
            // Eliminem el caràcter de tancament per afegir el id de referència. Donem per descomptat
            // que el tancament és >
            $tag = substr($tag, 0, strlen($tag)-1);

            if ($refId !=="0") {
                $tag .= ' data-wioccl-ref="' . $refId . '"';
            }

            $tag .= '>';

            return $tag;
        } else {
            return parent::getReplacement($position);
        }


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
