<?php
require_once "WiocclParser.php";

class WiocclReparseSet extends WiocclUpdatableInstruction{
    const PREFIX = '$$';
    const VAR_ATTR = "var";
    const VALUE_ATTR = "value";
    
    protected $rawVarName;
    protected $rawValue;

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);
        $this->updatablePrefix = self::FROM_REPARSESET;
        
        $this->rawVarName = $this->extractVarName($value, self::VAR_ATTR, true);
        $this->rawValue = $this->extractVarName($value, self::VALUE_ATTR, true);
    }
    
    protected function updateData($rightValue, $result="") {
        $varName = $this->normalizeArg(WiocclParser::parse($this->rawVarName, $this->arrays, $this->dataSource ));
        $v = $this->normalizeArg(WiocclParser::parse($this->rawValue, $this->arrays, $this->dataSource ));
        
        $this->newKeyValue = array("key" => $varName, "value" => $v);
    }
}