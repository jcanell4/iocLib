<?php
require_once "WiocclParser.php";

class WiocclReparse extends WiocclInstruction {
    const VAR_ATTR = "variables";

    protected function resolveOnClose($result) {
        $this->updateInstructions($this->updatableInstructions, TRUE);
        return WiocclParser::getValue($result, $this->arrays, $this->dataSource);
    }

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);

//        $varName = $this->extractVarName($value, self::VAR_ATTR);
    }
    
    public function updateParentArray($fromType, $key=NULL) {
        if($fromType !== self::FROM_REPARSESET){
            parent::updateParentArray($fromType, $key);
        }        
    }
    
    public function setUpdatableInstructions($ui){
      $this->updatableInstructions= $ui;
    }
}