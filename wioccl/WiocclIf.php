<?php

class WiocclIf extends WiocclInstruction{
    const COND_ATTR = "condition";

    protected $condition = false;

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);
        $value = str_replace("\\", "", $value);
        $this->condition = $this->evaluateCondition($this->extractVarName($value, self::COND_ATTR, true));

    }
    
    public function resolveOnClose($result){
        $this->updateInstructions($this->updatableInstructions, $this->condition);
        if($this->parentInstruction!==NULL){
            $this->setUpdatableInstructions($this->updatableInstructions);
        }
        if($this->condition){
            $ret = $result;
        }else{
            $ret = "";
        }
        return $ret;
    }

    public function setUpdatableInstructions($ui){
      $this->updatableInstructions= $ui;
    }

//    public function parseTokens($tokens, &$tokenIndex)
//    {
//
//        $result = '';
//
//        while ($tokenIndex < count($tokens)) {
//            $parsedValue = $this->parseToken($tokens, $tokenIndex);
//
//            if ($parsedValue === null) { // tancament del if
//                break;
//
//            } else {
//                $result .= $parsedValue;
//            }
//
//            ++$tokenIndex;
//        }
//
//
//        return ($this->condition ? $result : '');
//    }

    private function evaluateCondition($strCondition){
        $_condition = new _WiocclCondition($strCondition);
        $_condition->parseData($this->getArrays(), $this->getDataSource());
        return $_condition->validate();
        
    }
}