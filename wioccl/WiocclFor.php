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

        $this->pauseStructureGeneration();

        $this->from = $this->extractNumber($value, "from");
        $this->to = $this->extractNumber($value, "to");

        $this->resumeStructureGeneration();

        $this->wiocclLoop = new _WiocclLoop($this);
    }

    public function parseTokens($tokens, &$tokenIndex=0)
    {

        $result = $this->wiocclLoop->loop($tokens, $tokenIndex);

        $token = $tokens[$tokenIndex];
        $token['tokenIndex'] = $tokenIndex;

        // ALERTA! No passava pel resolveOnclose, el retorn es descarta
        $this->resolveOnClose($result, $token);

        return $result;
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