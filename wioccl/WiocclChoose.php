<?php

class WiocclChoose extends WiocclInstruction {

    const PREFIX = '__';
    const LEXPRESSION = 'lExpression';
    const REXPRESSION = 'rExpression';

    protected $chooseId;

    protected $lExpression;
    protected $rExpression;

    public function __construct($value = null, $arrays = [], $dataSource = []) {


        $this->chooseId = $this->extractVarName($value, "id", true);

        // obligatori
        $this->lExpression = $this->normalizeArg(WiocclParser::parse($this->extractVarName($value, self::LEXPRESSION, true), $arrays, $dataSource ));

        // opcional
        $aux = $this->extractVarName($value, self::REXPRESSION, false);
        if ($aux) {
            $this->rExpression = $this->normalizeArg(WiocclParser::parse($aux, $arrays, $dataSource));
        }


        $arrays[$this->chooseId] = $this->lExpression;

        parent::__construct($value, $arrays, $dataSource);

    }

    public function parseTokens($tokens, &$tokenIndex) {
        while ($tokenIndex < count($tokens)) {

            // Aquest valor no es fa servir, però serveix per determinar el tancament
            $parsedValue = $this->parseToken($tokens, $tokenIndex);

            if ($parsedValue == null) {
                break;
            }
            ++$tokenIndex;

        }

        // Comprovem si s'ha obtingut les condicions i valors dels case
        $cases = $this->arrays[self::PREFIX . $this->chooseId];

        // recorrem tots els casos fins trobar el primer que acompleixi la condició
        for ($i = 0; $i < count($cases); $i++) {

            $condition = $cases[$i]['condition']['lvalue'] . $cases[$i]['condition']['operator'] . $cases[$i]['condition']['rvalue'];

            $evaluation= $this->evaluateCondition($condition);

            if ($evaluation) {
                return $cases[$i]['value'];
            }
        }

        return '';
    }

    private function evaluateCondition($strCondition) {
        $_condition = new _WiocclCondition($strCondition);
        $_condition->parseData($this->getArrays(), $this->getDataSource());

        if (get_class($_condition->logicOp)== "_Literal") {
            return $_condition->validate() === true || $_condition->validate() === $this->lExpression || $_condition->validate() === $this->rExpression;
        } else {

            // Només es sobreescriu si no existeix
            $_condition->setValue1($this->lExpression, false);

            if ($this->rExpression !== NULL) {
                $_condition->setValue2($this->rExpression, false);
            }



            return $_condition->validate();
        }

    }
}