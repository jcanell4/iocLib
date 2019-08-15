<?php
require_once "WiocclParser.php";

class WiocclSet extends WiocclInstruction {
    const VAR_ATTR = "var";    
    const TYPE_ATTR = "type";    
    const MAP_ATTR = "map";    
    const VALUE_ATTR = "value";    
    const MAP_TYPE = "map";    
    const LITERAL_TYPE = "literal";    
    
    public function __construct($value = null, $arrays = [], $dataSource=[], &$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);

        $varName = $this->extractVarName($value, self::VAR_ATTR);
        $type = $this->extractVarName($value, self::TYPE_ATTR);
            $v = $this->normalizeArg(WiocclParser::getValue($this->extractVarName($value, self::VALUE_ATTR), $arrays, $dataSource)); 
        if($type === self::LITERAL_TYPE){
            $this->arrays[$varName] = $v;            
        }elseif ($type === self::MAP_TYPE) {
            $map = $this->extractMap($value, self::MAP_ATTR);
            $this->arrays[$varName] = $map[$v];            
        }
    }

    public function updateParentArray($fromType, $key=null) {
        if($fromType !== self::FROM_RESET){
            parent::updateParentArray($fromType, $key);
        }        
    }

    public function setUpdatableInstructions($ui){
      $this->updatableInstructions= $ui;
    }

}