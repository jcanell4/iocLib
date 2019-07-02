<?php

class WiocclChoose extends WiocclInstruction {

    protected $chooseId;
    protected $value;

    public function __construct($value = null, $arrays = [], $dataSource = []) {
        parent::__construct($value, $arrays, $dataSource);

        $this->chooseId = $this->extractVarName($value, "var", true);
        $this->value = $this->extractVarName($value, "value", true);

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
        $cases = $this->arrays[$this->chooseId];

        // recorrem tots els casos fins trobar el primer que acompleixi la condició

        for ($i = 0; $i < count($cases); $i++) {
            $condition = $this->evaluateCondition($cases[$i]['condition']);
            if ($condition) {
                return $cases[$i]['value'];
            }
        }

        return '';
    }

    private function evaluateCondition($strCondition) {
        $_condition = new _WiocclCondition($strCondition);
        $_condition->parseData($this->getArrays(), $this->getDataSource());

        $_condition->setValue1($this->value);
        // A la condició el paràmetre 1 ha de ser el $value
        return $_condition->validate();

    }
}