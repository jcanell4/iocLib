<?php
require_once "WiocclInstruction.php";

class WiocclSubset extends WiocclInstruction
{

    protected $varName;
    protected $fullArray = [];
    protected $itemName;

    protected $validator;

    public function __construct($value = null, $arrays = [], $dataSource, $generateSubset = true)
    {
        parent::__construct($value, $arrays, $dataSource);

        $this->varName = $this->parser->extractVarName($value, "subsetvar");
        $this->fullArray = $this->parser->extractArray($value);
        $this->itemName = $this->parser->extractVarName($value, "arrayitem");

        $this->validator = new _WiocclCondition($value, $this->parser);
        $this->parser->arrays[$this->varName] = $this->generateSubset();
    }


    protected function generateSubset()
    {
        $subset = [];

        foreach ($this->fullArray as $row) {

            $this->parser->arrays[$this->itemName] = $row;

            if (!$this->validator->validate()) {
                continue;
            }
            $subset[] = $row;
        }

        unset($this->parser->arrays[$this->itemName]);

        return $subset;

    }

}