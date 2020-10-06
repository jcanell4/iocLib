<?php
require_once "WiocclParser.php";

class WiocclLazyField extends WiocclInstruction {
    
    protected function resolveOnClose($result, $token) {

        $result = "{##$result##}";

        // Codi per afegir la estructura
        $class = (static::$parserClass);
        $class::close();
        $this->item->result  = $result;

        $this->rebuildRawValue($this->item, $this->currentToken['tokenIndex'], $token['tokenIndex']);

        return $result;
    }
}