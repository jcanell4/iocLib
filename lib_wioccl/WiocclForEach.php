<?php
require_once "WiocclParser.php";
require_once "_WiocclLoop.php";

class WiocclForEach extends WiocclParser
{

    protected $varName;
    protected $fullArray = [];

    protected $validator;
    protected $iterator;


    public function __construct($value = null, $arrays = [], $dataSource)
    {
        parent::__construct($value, $arrays, $dataSource);

        $this->varName = $this->extractVarName($value);
        $this->fullArray = $this->extractArray($value);
        $this->validator = new _WiocclCondition($value, $this);
        $this->iterator = new _WiocclLoop($value, $this);
    }

    public function parseTokens($tokens, &$tokenIndex = 0)
    {
        return $this->iterator->iterate($tokens, $tokenIndex, $this->fullArray, $this->validator);
    }

}