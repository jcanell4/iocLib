<?php
require_once "WiocclInstruction.php";
require_once "_WiocclLoop.php";

class WiocclForEach extends WiocclInstruction
{

    protected $varName;
    protected $fullArray = [];

    protected $validator;
    protected $iterator;


    public function __construct($value = null, $arrays = [], $dataSource)
    {
        parent::__construct($value, $arrays, $dataSource);

        $this->varName = $this->parser->extractVarName($value);
        $this->fullArray = $this->parser->extractArray($value);
        $this->validator = new _WiocclCondition($value, $this->parser);
        $this->iterator = new _WiocclLoop($value, $this->parser, $this->parser);
    }

    public function parseTokens($tokens, &$tokenIndex = 0)
    {
        return $this->iterator->loop($tokens, $tokenIndex, $this->fullArray, $this->validator);
    }

    public function getContent($token) {
        return $this->parser->getContent($token);
    }
}