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


        // Desactivem el parser pels atributs
        $class = (static::$parserClass);
        $prev = $class::$generateStructure;
        $class::$generateStructure = false;

        // obligatori
        $this->lExpression = $this->normalizeArg(WiocclParser::parse($this->extractVarName($value, self::LEXPRESSION, true), $arrays, $dataSource, $resetables ));

        // opcional
        $aux = $this->normalizeArg(WiocclParser::parse($this->extractVarName($value, self::REXPRESSION, false), $arrays, $dataSource, $resetables ));
        if ($aux) {
            $this->rExpression = $this->normalizeArg(WiocclParser::parse($aux, $arrays, $dataSource, $resetables));
        }


        $class::$generateStructure = $prev;

    }

    public function updateParentArray($fromType, $key=NULL){
        if($fromType !== self::FROM_CASE || $key !== self::PREFIX .$this->chooseId){
            parent::updateParentArray($fromType, $key);
        }
    }


    protected function resolveOnClose($result, $token) {
        // Comprovem si s'ha obtingut les condicions i valors dels case
        $cases = $this->arrays[self::PREFIX . $this->chooseId];

        $ret = $result;

        // recorrem tots els casos fins trobar el primer que acompleixi la condició
        for ($i = 0; $i < count($cases); $i++) {
            $lv = strlen($cases[$i]['condition']['lvalue'])===0?$this->lExpression:$cases[$i]['condition']['lvalue'];
            $rv = strlen($cases[$i]['condition']['rvalue'])===0?$this->rExpression:$cases[$i]['condition']['rvalue'];
            $op = strlen($cases[$i]['condition']['operator'])===0?"==":$cases[$i]['condition']['operator'];
            $condition = $lv . $op . $rv;

            $class = (static::$parserClass);
            $prev = $class::$generateStructure;
            $class::$generateStructure = false;

            $evaluation= $this->evaluateCondition($condition);

            $class::$generateStructure = $prev;

            $aux = $cases[$i]["resetables"];
            $ctx = $aux->RemoveLastContext(FALSE);

            if ($evaluation) {
                $this->resetables->updateData($ctx);
                $ret = $cases[$i];
                break;
//                return $cases[$i]['value'];
            }
        }

        // Codi per afegir la estructura
        $class = (static::$parserClass);
        $class::close();
        $this->item->result  = $ret;

        $this->rebuildRawValue($this->item, $this->currentToken['tokenIndex'], $token['tokenIndex']);
        return $ret;
    }

    public function parseTokens($tokens, &$tokenIndex=0) {
        while ($tokenIndex < count($tokens)) {

            // Aquest valor no es fa servir, però serveix per determinar el tancament
            $parsedValue = $this->parseToken($tokens, $tokenIndex);

            if ($parsedValue === null) {
                break;
            }
            ++$tokenIndex;

        }

        $auxToken = $tokens[$tokenIndex];
        $auxToken['tokenIndex'] = $tokenIndex;

        return $this->resolveOnClose("", $auxToken);
    }

    private function evaluateCondition($strCondition) {
        $_condition = new _WiocclCondition($strCondition);
        $_condition->parseData($this->getArrays(), $this->getDataSource(), $this->resetables);

        return $_condition->validate();
    }
}