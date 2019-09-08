<?php
class WiocclForEach extends WiocclInstruction implements WiocclLooperInstruction {
    protected $varName;
    protected $counterName;
    protected $fullArray =[];
    protected $filter;
    protected $wiocclLoop;
    
    const ARRAY_INDEX_ATTR = "counter";
    const FILTER_ATTR = "filter";

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);

        // varName correspón a la propietat var i es el nom de l'array
        // ALERTA! els arrays es llegeixen com un camp, la conversió d'array al seu valor es tracta al field
        $this->varName = $this->extractVarName($value);
        $this->counterName = $this->extractVarName($value, self::ARRAY_INDEX_ATTR, false);
        $this->fullArray = $this->extractArray($value);
        $strFilter = $this->extractVarName($value, self::FILTER_ATTR, false);
        if(empty($strFilter )){
            $strFilter = 'true';
        }
        $this->filter = new _WiocclCondition($strFilter);
        $this->wiocclLoop = new _WiocclLoop($this);
    }

    public function parseTokens($tokens, &$tokenIndex = 0)
    {
        return $this->wiocclLoop->loop($tokens, $tokenIndex);
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
        $row = $this->fullArray[$this->wiocclLoop->getCounter()];
        $this->setArrayValue($this->varName, $row);
        if(!empty($this->counterName)){
            $this->setArrayValue($this->counterName, $this->wiocclLoop->getCounter());
        }
    }

    public function validateLoop() {
        $this->filter->parseData($this->arrays, $this->dataSource);
        return $this->filter->validate();        
    }
 }