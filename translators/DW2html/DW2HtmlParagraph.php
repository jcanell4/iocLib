<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlParagraph extends DW2HtmlInstruction {


    public function getReplacement($position)
    {

        return parent::getReplacement($position);

        // ALERTA! Descartat perquè els paràgrafs només poden penjar de l'arrel i per consegüent
        // mai han de portar $refId, cal tenir en compte que s'ha de gestionar al frontend si el paràgraf
        // ha quedat buit quan s'han eliminat els nodes wioccl



        // El primer element del structure stack és el root, en aquest no cal afegir la referència
        if ($position == self::OPEN && count(WiocclParser::$structureStack) > 1) {

            $refId = WiocclParser::$structureStack[count(WiocclParser::$structureStack)-1];
            $tag = parent::getReplacement($position);
            // Eliminem el caràcter de tancament per afegir el id de referència. Donem per descomptat
            // que el tancament és >
            $tag = substr($tag, 0, strlen($tag)-1);
            $tag .= ' data-wioccl-ref="' . $refId . '">';

            return $tag;
        } else {
            return parent::getReplacement($position);
        }


    }

    public function isClosing($token) {
        // un doble salt de línia sempre tanca un paràgraf

        // Afegit el salt de línia simple, si no
        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
            || $token['raw'] === "\n\n" || $token['raw'] === "\n") {

            return true;
        } else {

            return false;
        }

    }
}
