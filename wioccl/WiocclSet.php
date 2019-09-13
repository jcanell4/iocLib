<?php
require_once "WiocclParser.php";

class WiocclSet extends WiocclInstruction {
    const VAR_ATTR = "var";    
    const TYPE_ATTR = "type";    
    const MAP_ATTR = "map";    
    const VALUE_ATTR = "value";    
    const MAP_TYPE = "map";    
    const LITERAL_TYPE = "literal";    
    
    public function __construct($value = null, $arrays = [], $dataSource=[], &$resetables=NULL, &$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        $rawVarName = $this->extractVarName($value, self::VAR_ATTR);
        $type = $this->extractVarName($value, self::TYPE_ATTR, FALSE);
        if(empty($type)){
            $type = self::LITERAL_TYPE;
        }
        $rawValue = $this->extractVarName($value, self::VALUE_ATTR);
        $varName = $this->normalizeArg(WiocclParser::parse($rawVarName, $arrays, $dataSource, $resetables ));
        $v = $this->normalizeArg(WiocclParser::parse($rawValue, $arrays, $dataSource, $resetables));

        if ($type === self::LITERAL_TYPE) {
            $this->resetables->setValue($varName, $v);
        } elseif ($type === self::MAP_TYPE) {
            $map = $this->extractMap($value, self::MAP_ATTR);
            $this->resetables->setValue($varName, $map[$v]);
        }            
    }
}
