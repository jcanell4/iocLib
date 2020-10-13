<?php
require_once "WiocclParser.php";

class WiocclLazyField extends WiocclInstruction {
    
    protected function resolveOnClose($result, $tokenEnd) {

        $result = "{##$result##}";

        $this->close($result, $tokenEnd);

        return $result;
    }

    // ALERTA! No està comprovat, això és només per generar la estructura. Copiat de WiocclField
    protected function splitOpeningAttrs(&$tag, &$attrs) {
        // el nom del camp es troba com atribut
        $tag .= "%s";
    }

    // ALERTA! No està comprovat, això és només per generar la estructura. Copiat de WiocclField
    protected function close($result, $tokenEnd) {

        parent::close($result, $tokenEnd);

        // Codi per afegir la estructura
        $this->generateRawValue($this->item->attrs, $this->currentToken['tokenIndex']+1, $tokenEnd['tokenIndex']-1);

    }
}