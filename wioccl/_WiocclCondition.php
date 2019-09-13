<?php

class _WiocclCondition {
    protected $strCondition;
    public $logicOp;

    public function __construct($strCondition) {
        $this->strCondition = empty($strCondition) ? 'false' : $strCondition;
        $this->logicOp = _LogicParser::getOperator($strCondition);
    }


    public function parseData($arrays, $dataSource, &$resetables) {
        $this->logicOp->parseData($arrays, $dataSource, $resetables);
    }

    public function validate() {
        return $this->logicOp->getValue();
    }

    public function setValue1($value, $override = true) {
        $this->logicOp->setValue1($value, $override);
    }

    public function setValue2($value, $override = true) {
        $this->logicOp->setValue2($value, $override);
    }


}

class _LogicParser {
//    protected $text;
//    function __construct($text) {
//        $this->text = $text;
//    }        

    public static function getOperator($text) {
        $ret = NULL;
//        if(preg_match('/\(.*\)/', $text, $matches) === 1){
//            
//        }
        $aOrOp = explode("||", $text, 2);
        if (count($aOrOp) > 1) {//OR
            $ret = new _OrOperation(_LogicParser::getOperator($aOrOp[0]), _LogicParser::getOperator($aOrOp[1]));
        } else {//AND
            $aAndOp = explode("&&", $text, 2);
            if (count($aAndOp) > 1) {
                $ret = new _AndOperation(_LogicParser::getOperator($aAndOp[0]), _LogicParser::getOperator($aAndOp[1]));
            } else if (preg_match('/[=!]=/', $text) === 1) {//CONDITION == o !=
                $ret = new _ConditionOperation($text);
            } else if (preg_match('/[><]=?/', $text) === 1) {//CONDITION <, >, <=, <=
                $ret = new _ConditionOperation($text);
            } else if (preg_match('/!/', $text) === 1) {// NotOperation
                $ret = new _NotOperation($text);
            } else if (preg_match('/ in /', $text) === 1) {// In, argument 1 (value) is in argument 2 (array)
                $ret = new _ConditionOperation($text);
            } else {//LITERAL
                $ret = new _Literal($text);
            }
        }
        return $ret;
    }
}

abstract class _LogicOperation {
    abstract function getValue();

    abstract function parseData($arrays, $datasource, &$resetables);

    protected function normalizeArg($arg) {
        if (strtolower($arg) == 'true') {
            return true;
        } else if (strtolower($arg) == 'false') {
            return false;
        } else if (is_int($arg)) {
            return intval($arg);
        } else if (is_numeric($arg)) {
            return floatval($arg);
        } else if (preg_match("/^\s*''(.*?)''\s*$/", $arg, $matches) === 1) {
            return $this->normalizeArg($matches[1]);
        } else {
            return $arg;
        }

    }
}

abstract class _BinaryOperation extends _LogicOperation {
    private $operator1;
    private $operator2;

    function __construct($op1, $op2 = NULL) {
        $this->operator1 = $op1;
        $this->operator2 = $op2;
    }

    public function getOperator1() {
        return $this->operator1;
    }

    public function getOperator2() {
        return $this->operator2;
    }

    public function setOperator1($operator1) {
        $this->operator1 = $operator1;
    }

    public function setOperator2($operator2) {
        $this->operator2 = $operator2;
    }

    public function parseData($arrays, $datasource, &$restables) {
        $this->operator1->parseData($arrays, $datasource, $resetables);
        if ($this->operator2 !== NULL) {
            $this->operator2->parseData($arrays, $datasource, $resetables);
        }
    }
}

class _Literal extends _LogicOperation {
    private $literal;
    private $value;

    function __construct($text) {
        $this->literal = $this->normalizeArg($text);
    }

    public function getValue() {

        // Si el literal es true o false ho retornem, quan es fa el parser dels valors es converteixen en enters.
        if ($this->literal === TRUE || $this->literal === FALSE) {
            return $this->literal;
        } else {
            return isset($this->value) ?  $this->normalizeArg($this->value) : TRUE;
        }


        // ALERTA[Xavi] aquesta era la implementació original, no es correcte perque 0 s'avalua com a false i per tant retorna true.
        //return $this->value ? $this->normalizeArg($this->value) : true;
    }

    public function parseData($arrays, $datasource, &$resetables) {
//        $this->value = (new WiocclParser($this->literal, $arrays, $datasource))->getValue();
        $this->value = WiocclParser::getValue($this->literal, $arrays, $datasource, $resetables);
    }
}

class _NotOperation extends _BinaryOperation {
    function __construct($operator1) {
        $no = substr($operator1, 1);
        parent::__construct(_LogicParser::getOperator($no));
    }

    public function getValue() {
        return !$this->getOperator1()->getValue();
    }
}

class _AndOperation extends _BinaryOperation {

    function __construct($operator1, $operator2) {
        parent::__construct($operator1, $operator2);
    }

    public function getValue() {
        return $this->getOperator1()->getValue() && $this->getOperator2()->getValue();
    }
}

class _OrOperation extends _BinaryOperation {

    function __construct($operator1, $operator2) {
        parent::__construct($operator1, $operator2);
    }

    public function getValue() {
        return $this->getOperator1()->getValue() || $this->getOperator2()->getValue();
    }
}

class _ConditionOperation extends _LogicOperation {
    private $operation;
    private $arg1;
    private $arg2;
    private $value1;
    private $value2;

    public function setValue1($value, $override = true) {
        if ($this->value1 == NULL || $override) {
            $this->value1 = $value;
        }
    }

    public function setValue2($value, $override = true) {

        if ($this->value2 == NULL || $override) {
            $this->value2 = $value;
        }

    }

    function __construct($expression) {
        $ac = $this->extractFilterArgs($expression);
        $this->arg1 = $ac[0];
        $this->arg2 = $ac[2];
        $this->operation = $ac[1];
    }

    public function parseData($arrays, $datasource, &$resetables) {
        $this->value1 = $this->normalizeArg(WiocclParser::parse($this->arg1, $arrays, $datasource, $resetables));
        $this->value2 = $this->normalizeArg(WiocclParser::parse($this->arg2, $arrays, $datasource, $resetables));
    }

    public function getValue() {
        return $this->resolveCondition($this->value1, $this->value2, $this->operation);
    }

    protected function extractFilterArgs($value) {
        if (preg_match('/(.*?)([><=!]=?| in )(.*)/', $value, $matches) === 1) {
            // ALERTA: Actualment el token amb > arriba tallat perquè l'identifica com a tancament del token d'apertura

            $arg1 = $matches[1];
            $arg2 = $matches[3];
            $operator = trim($matches[2]);


            return [$arg1, $operator, $arg2];
//            throw new Exception("Incorrect condition structure");
        };
        return null;
    }

    protected function resolveCondition($arg1, $arg2, $operator) {

        switch ($operator) {

            case '==':
                return $arg1 == $arg2;
            case '<=':
                return $arg1 <= $arg2;
            case '<':
                return $arg1 < $arg2;
            case '>=':
                return $arg1 >= $arg2;
            case '>':
                return $arg1 > $arg2;
            case '!=':
                return $arg1 != $arg2;

            case 'in':
                // el arg2 ha de ser un array
                return in_array($arg1, json_decode($arg2));

            default:
                return $arg1 && $arg2;
        }

    }

}