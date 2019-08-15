<?php
require_once "WiocclParser.php";

class WiocclReSet extends WiocclSet {

    const PREFIX = '$$';


    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$parentInstruction=NULL) {
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);

        $varName = $this->extractVarName($value, self::VAR_ATTR);
        $type = $this->extractVarName($value, self::TYPE_ATTR);
        $v = $this->normalizeArg(WiocclParser::getValue($this->extractVarName($value, self::VALUE_ATTR), $arrays, $dataSource));

        if (!isset($this->arrays[$varName])) {
            throw new Exception("S'ha de fer set d'una variable abans de poder reassignar el valor");
        }


//        $this->arrays[self::PREFIX . $varName] = true;

        if ($type === self::LITERAL_TYPE) {
            $this->arrays[$varName] = $v;
        } elseif ($type === self::MAP_TYPE) {
            $map = $this->extractMap($value, self::MAP_ATTR);
            $this->arrays[$varName] = $map[$v];
        }
        
        $this->updateParentArray(self::FROM_RESET, $varName);
    }

    public function updateParentArray($fromType, $key){
        self::stc_updateParentArray($this, $fromType, $key);
    }
}