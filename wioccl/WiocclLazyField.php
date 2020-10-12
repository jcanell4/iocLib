<?php
require_once "WiocclParser.php";

class WiocclLazyField extends WiocclInstruction {
    
    protected function resolveOnClose($result, $tokenEnd) {

        $result = "{##$result##}";

        $this->close($result, $tokenEnd);

        return $result;
    }
}