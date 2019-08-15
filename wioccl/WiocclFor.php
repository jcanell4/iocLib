<?php
class WiocclFor extends WiocclInstruction implements WiocclLooperInstruction{
    
    private $step = 1;
    private $from;
    private $to;
    private $counterName;
    private $wiocclLoop;

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);

        $this->counterName = $this->extractVarName($value, "counter");
        $this->from = $this->extractNumber($value, "from");
        $this->to = $this->extractNumber($value, "to");
        
        $this->wiocclLoop = new _WiocclLoop($this);
    }

    public function parseTokens($tokens, &$tokenIndex)
    {
        return $this->wiocclLoop->loop($tokens, $tokenIndex);
    }

    public function getFrom() {
        return $this->from;
    }

    public function getStep() {
        return $this->step;
    }

    public function getTo() {
        return $this->to;
    }

    public function updateLoop() {
        $this->arrays[$this->counterName] = $this->wiocclLoop->getCounter();        
    }

    public function validateLoop() {
        return true;
    }
}