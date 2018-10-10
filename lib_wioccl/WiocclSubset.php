<?php
require_once "WiocclParser.php";

class WiocclSubset extends WiocclParser
{

    protected $varName;
    protected $fullArray = [];
    protected $itemName;

    protected $validator;

    public function __construct($value = null, $arrays = [], $dataSource, $generateSubset = true)
    {
        parent::__construct($value, $arrays, $dataSource);

        $this->varName = $this->extractVarName($value, "subsetvar");
        $this->fullArray = $this->extractArray($value);
        $this->itemName = $this->extractVarName($value, "arrayitem");

        $this->validator = new _WiocclCondition($value, $arrays, $dataSource);
        $this->arrays[$this->varName] = $this->generateSubset();
    }


    protected function generateSubset()
    {
        $subset = [];

        foreach ($this->fullArray as $row) {

            $this->arrays[$this->itemName] = $row;

            if (!$this->validator->validate($this->arrays)) {
                continue;
            }
            $subset[] = $row;
        }

        unset($this->arrays[$this->itemName]);

        return $subset;

    }
}