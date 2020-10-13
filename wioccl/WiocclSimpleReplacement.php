<?php
require_once "WiocclParser.php";

class WiocclSimpleReplacement extends WiocclInstruction {

    const OPEN = 0;
    const CLOSE = 1;

    protected function resolveOnClose($result, $tokenEnd) {

        $result = $this->extra['replacement'][static::OPEN] . $result . $this->extra['replacement'][static::CLOSE];

        $this->close($result, $tokenEnd);

        return $result;
    }

    protected function splitOpeningAttrs(&$tag, &$attrs) {
        // no fem res, el replacement és la etiqueta d'apertura
    }
}