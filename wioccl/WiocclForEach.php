<?php
class WiocclForEach extends WiocclInstruction implements WiocclLooperInstruction {
    protected $varName;
    protected $counterName;
    protected $fullArray =[];
    protected $filter;
    protected $wiocclLoop;
    
    const ARRAY_INDEX_ATTR = "counter";
    const FILTER_ATTR = "filter";

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        // varName correspón a la propietat var i es el nom de l'array
        // ALERTA! els arrays es llegeixen com un camp, la conversió d'array al seu valor es tracta al field
        $this->varName = $this->extractVarName($value);
        $this->counterName = $this->extractVarName($value, self::ARRAY_INDEX_ATTR, false);

        // Desactivem el parseig pels continguts de l'array a iterar
        $class = static::$parserClass;
        $prev = $class::$generateStructure;
        $class::$generateStructure = false;

        $this->fullArray = $this->extractArray($value);

        $class::$generateStructure = $prev;


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
        $this->resolveOnClose($result, $token);

        // Codi per afegir la estructura
        $this->rebuildRawValue($this->item, $this->currentToken['tokenIndex'], $token['tokenIndex']);

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
        $row = $this->fullArray[$this->wiocclLoop->getCounter()];
        $this->setArrayValue($this->varName, $row);
        if(!empty($this->counterName)){
            $this->setArrayValue($this->counterName, $this->wiocclLoop->getCounter());
        }
    }

    public function validateLoop() {


        $class = static::$parserClass;
        $prev = $class::$generateStructure;
        $class::$generateStructure = false;

        $this->filter->parseData($this->arrays, $this->dataSource, $this->resetables);

        $class::$generateStructure = $prev;


        return $this->filter->validate();        
    }
 }