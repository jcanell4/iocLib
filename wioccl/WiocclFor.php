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

        $this->pauseStructureGeneration();

        $this->from = $this->extractNumber($value, "from");
        $this->to = $this->extractNumber($value, "to");
        $this->step = $this->extractNumber($value, "step", false, 1);
        $this->counterFromZero = $this->extractBoolean($value, "counterFromZero", false);

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