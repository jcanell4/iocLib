<?php
class WiocclFor extends WiocclInstruction implements WiocclLooperInstruction{

    private $step = 1;
    private $from;
    private $to;
    private $counterName;
    private $wiocclLoop;

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        $this->counterName = $this->extractVarName($value, "counter");

        // Desactivem el parser pels valors perquè podem provenir de camps
        $class = static::$parserClass;
        $prev = $class::$generateStructure;
        $class::$generateStructure = false;

        $this->from = $this->extractNumber($value, "from");
        $this->to = $this->extractNumber($value, "to");

        $class::$generateStructure = $prev;

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
        $this->arrays[$this->counterName] = $this->wiocclLoop->getCounter();
    }

    public function validateLoop() {
        return true;
    }
}