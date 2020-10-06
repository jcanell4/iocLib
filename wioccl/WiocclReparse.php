<?php
require_once "WiocclParser.php";

class WiocclReparse extends WiocclInstruction {
    const VAR_ATTR = "variables";

    protected function resolveOnClose($result) {

        $result = WiocclParser::getValue($result, $this->arrays, $this->dataSource, $this->resetables);

        // Codi per afegir la estructura
        $class = (static::$parserClass);
        $class::close();
        $this->item->result  = $result;

        return $result;
    }

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL ,&$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);
    }
}
