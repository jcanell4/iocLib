<?php
require_once "WiocclParser.php";
require_once "_WiocclLoop.php";

class WiocclFor extends WiocclParser {
    
    private $step = 1;
    private $from;
    private $to;
    private $counterName;

    protected $iterator;

    public function __construct($value = null, $arrays = [], $dataSource)
    {
        parent::__construct($value, $arrays, $dataSource);

        $this->counterName = $this->extractVarName($value, "counter");
        $this->from = $this->extractNumber($value, "from");
        $this->to = $this->extractNumber($value, "to");

        $this->iterator = new _WiocclLoop($value, $this);
    }

    public function parseTokens($tokens, &$tokenIndex=0) {
        return $this->iterator->loop($tokens, $tokenIndex, $this->from, $this->to);
    }



}