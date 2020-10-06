<?php
require_once "WiocclParser.php";

class WiocclReparse extends WiocclInstruction {
    const VAR_ATTR = "variables";

    protected function resolveOnClose($result, $token) {

        $result = WiocclParser::getValue($result, $this->arrays, $this->dataSource, $this->resetables);

        // Codi per afegir la estructura
        $this->close($result, $token);

        return $result;
    }

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL ,&$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);
    }
}
