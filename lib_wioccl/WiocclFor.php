<?php
require_once "WiocclInstruction.php";
require_once "_WiocclLoop.php";

class WiocclFor extends WiocclInstruction {

    private $from;
    private $to;
    private $counterName;

    protected $iterator;

    public function __construct($value = null, $arrays = [], $dataSource)
    {
        parent::__construct($value, $arrays, $dataSource);

        $this->counterName = $this->parser->extractVarName($value, "counter");
        $this->from = $this->parser->extractNumber($value, "from");
        $this->to = $this->parser->extractNumber($value, "to");

        $this->iterator = new _WiocclLoop($value, $this->parser);
    }

    public function parseTokens($tokens, &$tokenIndex=0) {
        return $this->iterator->loop($tokens, $tokenIndex, $this->from, $this->to);
    }



}