<?php
require_once "WiocclParser.php";

class WiocclReSet extends WiocclSet {

    public $updateParentArray = true;

    public function __construct($value = null, $arrays = [], $dataSource = []) {
        parent::__construct($value, $arrays, $dataSource);

        $varName = $this->extractVarName($value, self::VAR_ATTR);
        $type = $this->extractVarName($value, self::TYPE_ATTR);
        $v = $this->normalizeArg(WiocclParser::getValue($this->extractVarName($value, self::VALUE_ATTR), $arrays, $dataSource));

        if (!isset($this->arrays[$varName])) {
            throw new Exception("S'ha de fer set d'una variable abans de poder reassignar el valor");
        }

        if ($type === self::LITERAL_TYPE) {
            $this->arrays[$varName] = $v;
        } elseif ($type === self::MAP_TYPE) {
            $map = $this->extractMap($value, self::MAP_ATTR);
            $this->$arrays[$varName] = $map[$v];
        }
    }
}