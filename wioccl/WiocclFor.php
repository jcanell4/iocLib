<?php
class WiocclFor extends WiocclInstruction implements WiocclLooperInstruction{

    private $step;
    private $from;
    private $to;
    private $counterName;
    private $wiocclLoop;
    protected $counterFromZero=FALSE;

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        $this->counterName = $this->extractVarName($value, "counter");
        $this->from = $this->extractNumber($value, "from");
        $this->to = $this->extractNumber($value, "to");
        $this->step = $this->extractNumber($value, "step", false, 1);
        $this->counterFromZero = $this->extractBoolean($value, "counterFromZero", false);

        $this->wiocclLoop = new _WiocclLoop($this);
    }

    public function parseTokens($tokens, &$tokenIndex=0)
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
        if($this->counterFromZero){
            $this->setArrayValue($this->counterName, $this->wiocclLoop->getCounter());
        }else{
            $this->setArrayValue($this->counterName, $this->wiocclLoop->getIndex());
        }
    }

    public function validateLoop() {
        return true;
    }
}