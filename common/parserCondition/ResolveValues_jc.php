<?php

abstract class abstractResolveValues_jc {
    protected $resolvers = [
                "rslvResolveFunction_jc",
                "rslvResolveArray_jc",
                "rslvResolveObject_jc",
                "rslvResolveQString_jc",
                "rslvResolveLiteral_jc",
                "rslvResolveString_jc"
            ];
    protected $className;
    protected $firstPattern;
    protected $lastPattern;
    protected $toParse;

    //public abstract function configClass();

   /**
    Dóna els nom de la classe, el paró inicial i el final
   */
    protected function initClass($className, $firstPattern, $lastPattern){
        $this->className = $className;
        $this->firstPattern = $firstPattern;
        $this->lastPattern = $lastPattern;
    }

    public function getClassName() {
        return $this->className;
    }

    public function getFirstPattern() {
        return $this->firstPattern;
    }

    public function getLastPattern() {
        return $this->lastPattern;
    }

    /**
    Manté el que encara queda per analaitzar
    */
    public function getToParse() {
        return $this->toParse;
    }

    public function setToParse($toParse) {
        $this->toParse = $toParse;
    }

    /**
    Assigna els parametres a partir d'un array amb els valors extrets després de passar el firstPattern a toParser
    */
    //public abstract function setParamsFromFirstMatcher($params);

    /**
    Assigna els parametres a partir d'un array amb els valors extrets després de passar el lastPattern a toParser
    */
    //public abstract function setParamsFromLastMatcher($params);

    /**
    Obté el valor de la instrucció un cop s'ha fet parse()
    */
    //public abstract function getValue();

    /**
    Analitza el que queda per analitzar (toParser) creant tots els resolvers que calgui per tal de poder obtenir el valor final amb getValue()
    */
    //public abstract function parse();

//    public function SUMA($param) {
//        $result = 0;
//        foreach ($param as $elem) {
//            $result += $elem;
//        }
//        return $result;
//    }

    /**
   Busca quina és la instancia que pot resoldre la dada que es troba més a l'esquerra de toParser
   */
    public function resolveInstance($toParse) {
        $ret = NULL;
        foreach ($this->resolvers as $resolver) {
            $matcher = [];
            $instance = new $resolver();
            $instance->configClass();
            if (call_user_func_array([$instance, 'firstMatch'], array($toParse, &$matcher))) {
                $instance->setParamsFromFirstMatcher($matcher);
                $ret = $instance;
                break;
            }
        }
        return $ret;
    }

    public function firstMatch($toParser, &$matcher) {
        return (bool)(preg_match($this->getFirstPattern(), $toParser, $matcher));
    }

    public function lastMatch($toParser, &$matcher) {
        return (bool)(preg_match($this->getLastPattern(), $toParser, $matcher));
    }

}

class resolveValueFromInstruction extends abstractResolveValues_jc {

    public function resolveValue($toParse) {
        $instance = $this->resolveInstance($toParse);
        $instance->parse();
        $value = $instance->getValue();
        return $value;
    }

}


/**
Classe abstracta de la que hereten totes aquelles que tenen dades compostes i cal resoldre-les amb una pila
*/
abstract class stackResolveValues_jc extends abstractResolveValues_jc {
    private $pila;

    protected function getArrayValuesFromStack(){
        $values = [];
        foreach ($this->pila as $resolver) {
            $values[] = $resolver->getValue();
        }
        return $values;
    }

    public function parse() {
        $exit = false;
        while(!$exit) {
            $matcher = [];
            $instance = $this->resolveInstance($this->getToParse());
            $this->pila[] = $instance;
            $instance->parse();
            $exit = call_user_func_array([$this, 'lastMatch'], array($instance->toParse, &$matcher));
            if ($exit) {
                $this->setParamsFromLastMatcher($matcher);
            }else {
                $this->setToParse($instance->toParse);
            }
        }
    }
}

class rslvResolveFunction_jc extends stackResolveValues_jc {
    private $functionName;

    public function configClass(){
        $this->initClass("rslvResolveFunction_jc", '/^(\w+)(\()(.*)$/u', '/^(\))(.*)$/');
    }

    public function setParamsFromFirstMatcher($params){
        $this->functionName = trim($params[1]);
        $this->setToParse(trim($params[3]));
    }

    public function setParamsFromLastMatcher($params){
        $this->setToParse(trim($params[2]));
    }

    public function getValue() {
        $result = call_user_func_array(["IocCommonFunctions", $this->functionName], $this->getArrayValuesFromStack());
        return $result;
    }
}

class rslvResolveArray_jc extends stackResolveValues_jc {
    public function configClass(){
        $this->initClass("rslvResolveArray_jc", '/^(\[)(.*)$/', '/^(\])(.*)$/');
    }

    public function setParamsFromFirstMatcher($params){
        $this->resolvers = ["rslvResolveArrayItem_jc"];
        $this->setToParse(trim($params[2]));
    }

    public function setParamsFromLastMatcher($params){
        $this->setToParse(trim($params[2]));
    }

    public function getValue() {
        $result = $this->getArrayValuesFromStack();
        return $result;
    }
}



class rslvResolveObject_jc extends stackResolveValues_jc {
    public function configClass(){
        $this->initClass("rslvResolveObject_jc", '/^({)(.*)$/', '/^(})(.*)/');
    }

    public function setParamsFromFirstMatcher($params){
        $this->resolvers = ["rslvResolveObjectField_jc"];
        $this->setToParse(trim($params[2]));
    }

    public function setParamsFromLastMatcher($params){
        $this->setToParse(trim($params[2]));
    }

    public function getValue() {
        $result = [];
        $resolveFileds = $this->getArrayValuesFromStack();
        foreach ($resolveFileds as $resolver) {
            $result[$resolver->getKey()]= $resolver->getValue();
        }
        return $result;
    }
}

class abstractResolveSeparatedValue extends abstractResolveValues_jc {
    protected $resolver;

    public function configClass(){}

    protected function initClass($className, $comaSep=",", $firstPattern='/^(.*)$/'){
        $lastPat = "/^[".$comaSep."]?(.*)/";
        parent::initClass($className,  $firstPattern, $lastPat);
    }

    public function parse(){
        $matcher = [];
        $this->resolver = $this->resolveInstance($this->getToParse());
        $this->resolver->parse();
        $exit = call_user_func_array([$this, 'lastMatch'], array($this->resolver->getToParse(), &$matcher));
        if ($exit) {
            $this->setParamsFromLastMatcher($matcher);
        }
    }

    public function setParamsFromFirstMatcher($params){
        $this->setToParse(trim($params[1]));
    }

    public function setParamsFromLastMatcher($params){
        $this->setToParse(trim($params[1]));
    }

    public function getValue() {
        return $this->resolver->getValue();
    }

}

class rslvResolveObjectField_jc extends abstractResolveSeparatedValue {
    protected $key;

    public function configClass(){
        $this->initClass("rslvResolveObjectField_jc", ",", '/^(\w+)(\:)(.*)$/u');
    }

    public function setParamsFromFirstMatcher($params){
        $this->key = $params[1];
        $this->setToParse($params[3]);
    }

    public function getKey() {
        return $this->key;
    }

    public function setKey($v){
        $this->key = $v;
    }
}

class rslvResolveArrayItem_jc extends abstractResolveSeparatedValue {

    public function configClass(){
        $this->initClass("rslvResolveArrayItem_jc");
    }

}



class rslvResolveLiteral_jc extends abstractResolveValues_jc {
    protected $value;
    protected $rawvalue;

    public function configClass(){
        $this->initClass("rslvResolveLiteral_jc", '/^(?>,)?((?:\d+\.?\d*)|(?:[Tt][Rr][Uu][Ee])|(?:[Ff][Aa][Ll][Ss][Ee]))(.*)$/', '/^(.*)$/');
    }

    public function setParamsFromFirstMatcher($params){
        $this->rawvalue =  trim($params[1]);
        $this->setToParse(trim($params[2]));
    }

    public function setParamsFromLastMatcher($params){
        $this->setToParse(trim($params[1]));
    }

    public function parse(){
        if (preg_match("/^\d+\.\d+$/", $this->rawvalue)){
            $this->setValue((float)$this->rawvalue);
        }else if(preg_match("/^\d+$/", $this->rawvalue)){
            $this->setValue((float)$this->rawvalue);
        }else{
            $this->setValue(filter_var($this->rawvalue, FILTER_VALIDATE_BOOLEAN));
        }
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($v){
        $this->value = $v;
    }
}

class rslvResolveQString_jc extends rslvResolveLiteral_jc {

    public function configClass(){
        $this->initClass("rslvResolveQString_jc", '/^(".*?[^\\\\]")(.*)$/', '/^(.*)$/');
    }

    public function setParamsFromFirstMatcher($params){
        $this->rawvalue = trim($params[1]);
        $this->setToParse(trim($params[2]));
    }

    public function setParamsFromLastMatcher($params){
        $this->setToParse(trim($params[1]));
    }

    public function parse(){
        $this->setValue(substr($this->rawvalue,1,-1));
    }
}

class rslvResolveString_jc extends rslvResolveQString_jc {

    public function configClass(){
        $this->initClass("rslvResolveString_jc", '/^(?>,)?(\w+)(,|\)|\]|}|$)(.*)$/u', '/^(.*)$/');
    }

    public function setParamsFromFirstMatcher($params){
        $this->rawvalue = trim($params[1]);
        $this->setToParse(trim($params[3]));
    }

    public function parse(){
        $this->setValue($this->rawvalue);
    }

}
