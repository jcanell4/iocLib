<?php

class WiocclIf extends WiocclInstruction{
    const COND_ATTR = "condition";

    protected $condition = false;

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);
        $this->resetables->addNewContext();
        $value = str_replace("\\", "", $value);
        $this->condition = $this->evaluateCondition($this->extractVarName($value, self::COND_ATTR, true));
    }
    
    public function resolveOnClose($result){
        if($this->condition){
            $ret = $result;
        }else{
            $ret = "";
        }

        $this->resetables->RemoveLastContext($this->condition);
        return $ret;
    }

    private function evaluateCondition($strCondition){
        $_condition = new _WiocclCondition($strCondition);
        $_condition->parseData($this->getArrays(), $this->getDataSource(), $this->resetables);
        return $_condition->validate();
        
    }
}