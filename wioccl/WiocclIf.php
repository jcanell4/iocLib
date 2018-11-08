<?php

class WiocclIf extends WiocclInstruction{
    const COND_ATTR = "condition";

    protected $condition = false;

    public function __construct($value = null, $arrays = [], $dataSource=[])
    {
        parent::__construct($value, $arrays, $dataSource);
        $value = str_replace("\\", "", $value);
        $this->condition = $this->evaluateCondition($this->extractVarName($value, self::COND_ATTR, true));

    }

    public function parseTokens($tokens, &$tokenIndex)
    {

        $result = '';

        while ($tokenIndex < count($tokens)) {
            $parsedValue = $this->parseToken($tokens, $tokenIndex);

            if ($parsedValue === null) { // tancament del if
                break;

            } else {
                $result .= $parsedValue;
            }

            ++$tokenIndex;
        }


        return ($this->condition ? $result : '');
    }

    private function evaluateCondition($strCondition){
        $_condition = new _WiocclCondition($strCondition);
        $_condition->parseData($this->getArrays(), $this->getDataSource());
        return $_condition->validate();
        
    }
}