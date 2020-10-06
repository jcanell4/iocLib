<?php
require_once "WiocclParser.php";

class WiocclLazyField extends WiocclInstruction {
    
    protected function resolveOnClose($result, $token) {

        $result = "{##$result##}";

        $this->close($result, $token);

        return $result;
    }
}