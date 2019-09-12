<?php

class WiocclChoose extends WiocclInstruction {

    const PREFIX = '__';
    const LEXPRESSION = 'lExpression';
    const REXPRESSION = 'rExpression';

    protected $chooseId;

    protected $lExpression;
    protected $rExpression;

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL) {


        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        $this->chooseId = $this->extractVarName($value, "id", true);

        // obligatori
        $this->lExpression = $this->normalizeArg(WiocclParser::parse($this->extractVarName($value, self::LEXPRESSION, true), $arrays, $dataSource, $resetables ));

        // opcional
        $aux = $this->normalizeArg(WiocclParser::parse($this->extractVarName($value, self::REXPRESSION, false), $arrays, $dataSource, $resetables ));
        if ($aux) {
            $this->rExpression = $this->normalizeArg(WiocclParser::parse($aux, $arrays, $dataSource, $resetables));
        }
    }
    
    public function updateParentArray($fromType, $key=NULL){
        if($fromType !== self::FROM_CASE || $key !== self::PREFIX .$this->chooseId){
            parent::updateParentArray($fromType, $key);
        }
    }

    
    protected function resolveOnClose($result) {
        // Comprovem si s'ha obtingut les condicions i valors dels case
        $cases = $this->arrays[self::PREFIX . $this->chooseId];

        // recorrem tots els casos fins trobar el primer que acompleixi la condició
        for ($i = 0; $i < count($cases); $i++) {
            $lv = strlen($cases[$i]['condition']['lvalue'])===0?$this->lExpression:$cases[$i]['condition']['lvalue'];
            $rv = strlen($cases[$i]['condition']['rvalue'])===0?$this->rExpression:$cases[$i]['condition']['rvalue'];
            $op = strlen($cases[$i]['condition']['operator'])===0?"==":$cases[$i]['condition']['operator'];
            $condition = $lv . $op . $rv;

            $evaluation= $this->evaluateCondition($condition);
            
            $aux = $cases[$i]["resetables"];
            $ctx = $aux->RemoveLastContext(FALSE);

            if ($evaluation) {
                $this->resetables->updateData($ctx);
                return $cases[$i]['value'];
            }
        }

        return $result;
    }

    public function parseTokens($tokens, &$tokenIndex) {
        while ($tokenIndex < count($tokens)) {

            // Aquest valor no es fa servir, però serveix per determinar el tancament
            $parsedValue = $this->parseToken($tokens, $tokenIndex);

            if ($parsedValue === null) {
                break;
            }
            ++$tokenIndex;

        }
        return $this->resolveOnClose("");
    }

    private function evaluateCondition($strCondition) {
        $_condition = new _WiocclCondition($strCondition);
        $_condition->parseData($this->getArrays(), $this->getDataSource(), $this->resetables);
        
        return $_condition->validate();
    }
}