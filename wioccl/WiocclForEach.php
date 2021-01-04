<?php
class WiocclForEach extends WiocclInstruction implements WiocclLooperInstruction {
    protected $varName;
    protected $counterName;
    protected $fullArray =[];
    protected $filter;
    protected $wiocclLoop;
    protected $counterFromZero=FALSE;
    
    const ARRAY_INDEX_ATTR = "counter";
    const COUNTER_FROM_ZERO_ATTR = "counterFromZero";
    const FILTER_ATTR = "filter";

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        $this->varName = $this->extractVarName($value);
        $this->counterName = $this->extractVarName($value, self::ARRAY_INDEX_ATTR, false);

        $this->pauseStructureGeneration();

        $this->fullArray = $this->extractArray($value);
        $this->counterFromZero = $this->extractBoolean($value, self::COUNTER_FROM_ZERO_ATTR, false);

        $this->resumeStructureGeneration();


        $strFilter = $this->extractVarName($value, self::FILTER_ATTR, false);
        if(empty($strFilter )){
            $strFilter = 'true';
        }
        $this->filter = new _WiocclCondition($strFilter);
        $this->wiocclLoop = new _WiocclLoop($this);
    }

    public function parseTokens($tokens, &$tokenIndex = 0)
    {
        // Afegit per controlar el tancament
        $result = $this->wiocclLoop->loop($tokens, $tokenIndex);

        $token = $tokens[$tokenIndex];
        $token['tokenIndex'] = $tokenIndex;

        // ALERTA! No passava pel resolveOnclose, el retorn es descarta
        $result = $this->resolveOnClose($result, $token);

        return $result;
//        return $this->wiocclLoop->loop($tokens, $tokenIndex);
    }

    public function getFrom() {
        return 0;        
    }

    public function getStep() {
        return 1;
    }

    public function getTo() {
        return count($this->fullArray)-1;
    }

    public function updateLoop() {
        $row = $this->fullArray[$this->wiocclLoop->getIndex()];
        $this->setArrayValue($this->varName, $row);
        if(!empty($this->counterName)){
            if($this->counterFromZero){
                $this->setArrayValue($this->counterName, $this->wiocclLoop->getCounter());
            }else{
                $this->setArrayValue($this->counterName, $this->wiocclLoop->getIndex());
            }
        }
    }

    public function validateLoop() {


        $this->pauseStructureGeneration();

        $this->filter->parseData($this->arrays, $this->dataSource, $this->resetables);

        $this->resumeStructureGeneration();


        return $this->filter->validate();        
    }


}
