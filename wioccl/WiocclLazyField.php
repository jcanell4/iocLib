<?php
require_once "WiocclParser.php";

class WiocclLazyField extends WiocclInstruction {
    
    protected function resolveOnClose($result) {
        return "{##$result##}";
    }
}