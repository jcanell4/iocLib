<?php

class _WiocclCondition{
    protected $strCondition;
    protected $logicOp;

    public function __construct($strCondition){
        $this->strCondition = empty($strCondition)?'false':$strCondition;
        $this->logicOp = _LogicParser::getOperator($strCondition);
    }


    public function parseData($arrays, $dataSource){
        $this->logicOp->parseData($arrays, $dataSource);
    }
    
    public function validate(){
        return $this->logicOp->getValue();
    }

    public function setValue1($value) {
        $this->logicOp->setValue1($value);
    }

    public function setValue2($value) {
        $this->logicOp->setValue2($value);
    }

}

class _LogicParser{
//    protected $text;
//    function __construct($text) {
//        $this->text = $text;
//    }        
    
    public static function getOperator($text){
        $ret=NULL;
//        if(preg_match('/\(.*\)/', $text, $matches) === 1){
//            
//        }
        $aOrOp = explode("||", $text, 2);
        if(count($aOrOp)>1){//OR
            $ret = new _OrOperation(_LogicParser::getOperator($aOrOp[0]), _LogicParser::getOperator($aOrOp[1]));
        }else{//AND
            $aAndOp = explode("&&", $text, 2);
            if(count($aAndOp)>1){
                $ret = new _AndOperation(_LogicParser::getOperator($aAndOp[0]), _LogicParser::getOperator($aAndOp[1]));
            }else if(preg_match('/[=!]=/', $text) === 1){//CONDITION == o !=
                $ret = new _ConditionOperation($text);
            }else if(preg_match('/[><]=?/', $text) === 1){//CONDITION <, >, <=, <=
                $ret = new _ConditionOperation($text);
            }else if(preg_match('/!/', $text) === 1){// NotOperation
                $ret = new _NotOperation(_LogicParser::getOperator($text));
            }else if(preg_match('/ in /', $text) === 1){// In, argument 1 (value) is in argument 2 (array)
                $ret = new _ConditionOperation($text);
            }else{//LITERAL
                $ret = new _Literal($text);
            }
        }
        return $ret;
    }
}

abstract class _LogicOperation{
    abstract function getValue();
    
    abstract function parseData($arrays, $datasource);
    
    protected function normalizeArg($arg){
        if (strtolower($arg) == 'true') {
            return true;
        } else if (strtolower($arg) == 'false') {
            return false;
        } else if (is_int($arg)) {
            return intval($arg);
        } else if (is_numeric($arg)) {
            return floatval($arg);
        } else if (preg_match("/^''(.*?)''$/", $arg, $matches) === 1) {
            return $this->normalizeArg($matches[1]);
        } else {
            return $arg;
        }

    }    
}

abstract class _BinaryOperation extends _LogicOperation{    
    private $operator1;
    private $operator2;

    function __construct($op1, $op2=NULL) {
        $this->operator1 = $op1;
        $this->operator2 = $op2;
    }

    public function getOperator1(){
        return $this->operator1;
    }
    
    public function getOperator2(){
        return $this->operator2;
    }

    public function setOperator1($operator1){
        $this->operator1 = $operator1;
    }
    
    public function setOperator2($operator2){
        $this->operator2 = $operator2;
    }
    
    public function parseData($arrays, $datasource){
        $this->operator1->parseData($arrays, $datasource);
        if($this->operator2!==NULL){
            $this->operator2->parseData($arrays, $datasource);
        }
    }
}

class _Literal extends _LogicOperation{
    private $literal;
    private $value;
    
    function __construct($text) {
        $this->literal = $this->normalizeArg($text);
    }

    public function getValue() {
        return $this->value?$this->normalizeArg($this->value):true;
    }
    
    public function parseData($arrays, $datasource) {
//        $this->value = (new WiocclParser($this->literal, $arrays, $datasource))->getValue();
        $this->value = WiocclParser::getValue($this->literal, $arrays, $datasource);
    }
}

class _NotOperation extends _BinaryOperation{
    function __construct($operator1) {
        parent::__construct($operator1);
    }

    public function getValue() {
        return !$this->getOperator1()->getValue();
    }
}

class _AndOperation extends _BinaryOperation{
    
    function __construct($operator1, $operator2) {
        parent::__construct($operator1, $operator2);
    }

    public function getValue() {
        return $this->getOperator1()->getValue() && $this->getOperator2()->getValue();
    }
}

class _OrOperation extends _BinaryOperation{
    
    function __construct($operator1, $operator2) {
        parent::__construct($operator1, $operator2);
    }

    public function getValue() {
        return $this->getOperator1()->getValue() || $this->getOperator2()->getValue();
    }
}

class _ConditionOperation extends _LogicOperation{
    private $operation;
    private $arg1;
    private $arg2;
    private $value1;
    private $value2;

    public function setValue1($value) {
        $this->value1 = $value;
    }

    public function setValue2($value) {
        $this->value2 = $value;
    }
            
    function __construct($expression) {
        $ac = $this->extractFilterArgs($expression);
        $this->arg1 = $ac[0];
        $this->arg2 = $ac[2];
        $this->operation = $ac[1];
    }

    public function parseData($arrays, $datasource) {
//        $this->value1 = $this->normalizeArg((new WiocclParser($this->arg1, $arrays, $datasource))->getValue());
//        $this->value2 = $this->normalizeArg((new WiocclParser($this->arg2, $arrays, $datasource))->getValue());
        $this->value1 = $this->normalizeArg(WiocclParser::getValue($this->arg1, $arrays, $datasource));
        $this->value2 = $this->normalizeArg(WiocclParser::getValue($this->arg2, $arrays, $datasource));
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

    protected function resolveCondition($arg1, $arg2, $operator)
    {

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