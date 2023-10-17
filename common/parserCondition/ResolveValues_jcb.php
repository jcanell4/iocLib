<?php

abstract class abstractResolveValues_jcb {
    protected static $resolvers = [
                "rslvResolveFunction_jcb",
                "rslvResolveString_jcb",
                "rslvResolveArray_jcb",
                "rslvResolveObject_jcb",
                "rslvResolveLiteral_jcb"
            ];
    protected $className;
    protected $toParse;

   /**
    Dóna els nom de la classe, el paró inicial i el final
   */
    protected function initClass($className){
        $this->className = $className;
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
        return $this->toParse = $toParse;
    }

    /**
    Assigna els parametres a partir d'un array amb els valors extrets després de passar el firstPattern a toParser
    */
    public abstract function setParamsFromFirstMatcher($toParse);

    /**
    Assigna els parametres a partir d'un array amb els valors extrets després de passar el lastPattern a toParser
    */
    public abstract function setParamsFromLastMatcher($toParse);

    /**
    Obté el valor de la instrucció un cop s'ha fet parse()
    */
    public abstract function getValue();

    /**
    Analitza el que queda per analitzar (toParser) creant tots els resolvers que calgui per tal de poder obtenir el valor final amb getValue()
    */
   public abstract function parse();

    /**
   Busca quina és la instancia que pot resoldre la dada que es troba més a l'esquerra de toParser
   */
    public static function resolveInstance($toParse) {
        foreach (self::$resolvers as $resolver) {
            if (call_user_func([$resolver, 'firstMatch'], $resolver, $toParse)) {
                $instance = new $resolver();
                $instance->setParamsFromFirstMatcher($toParse);
                break;
            }
        }
        return $instance;
    }

    public static function firstMatch($resolver, $toParse) {
        return (bool)(preg_match($resolver::$firstPattern, $toParse));
    }

    public static function lastMatch($resolver, $toParse) {
        return (bool)(preg_match($resolver::$lastPattern, $toParse));
    }

}

/**
Classe abstracta de la que hereten totes aquelles que tenen dades compostes i cal resoldre-les amb una pila
*/
abstract class stackResolveValues_jcb extends abstractResolveValues_jcb {

    protected function getArrayValuesFromStack(){
        $values = [];
        foreach ($this->pila as $resolver) {
            $values[] = $resolver->getValue();
        }
        return $values;
    }

    public function parse() {
        $exit=false;
        while(!$exit) {
            $instance = self::resolveInstance($this->getToParse());
            $pila[] = $instance;
            $instance->parse();
            $class = $instance->getClassName();
            $toParse = $instance->getToParse();
            $exit = call_user_func([$class, 'lastMatch'], $class, $toParse);
            $instance->setParamsFromLastMatcher($toParse);
        }
        return $pila;
    }
}

class ResolveValue_jcb extends stackResolveValues_jcb {

    public function init_parse($toParse) {
        $instance = $this->resolveInstance($toParse);
        $parse = $instance->parse();
        $value = $instance->getValue();
        return $value;
    }

    public function setParamsFromFirstMatcher($toParse) {}
    public function setParamsFromLastMatcher($toParse) {}
    public function getValue() {}
}

class rslvResolveFunction_jcb extends stackResolveValues_jcb {
    public static $firstPattern = '/^(\w+)(\()(.*)$/';
    public static $lastPattern = '/^(\))(.*)$/';
    private $functionName;

    public function setParamsFromFirstMatcher($toParse){
        preg_match(self::$firstPattern, $toParse, $matcher);
        $this->initClass("rslvResolveFunction_jcb");
        $this->functionName = trim($matcher[1]);
        $this->setToParse(trim($matcher[3]));
    }

    public function setParamsFromLastMatcher($toParse){
        preg_match(self::$lastPattern, $toParse, $matcher);
        $this->toParse = trim($matcher[2]);
    }

    public function getValue() {
        $result = call_user_func_array(["IocCommonFunctions", $this->functionName], $this->getArrayValuesFromStack());
        return $result;
    }
}

class rslvResolveArray_jcb extends stackResolveValues_jcb {
    public static $firstPattern = '/^(\[)(.*)$/';
    public static $lastPattern = '/^(\])(.*)$/';

    public function setParamsFromFirstMatcher($toParse){
        preg_match(self::$firstPattern, $toParse, $matcher);
        $this->initClass("rslvResolveArray_jcb");
        $this->resolvers = ["rslvResolveArrayItem_jcb"];
        $this->setToParse(trim($matcher[2]));
    }

    public function setParamsFromLastMatcher($toParse){
        preg_match(self::$lastPattern, $toParse, $matcher);
        $this->toParse = trim($matcher[2]);
    }

    public function getValue() {
        $result = $this->getArrayValuesFromStack();
        return $result;
    }
}



class rslvResolveObject_jcb extends stackResolveValues_jcb {
    public static $firstPattern = '/^,?(\{)(.*)$/';
    public static $lastPattern = '/^(\})(.*)/';

    public function setParamsFromFirstMatcher($toParse){
        preg_match(self::$firstPattern, $toParse, $matcher);
        $this->initClass("rslvResolveObject_jcb");
        $this->resolvers = ["rslvResolveObjectField_jcb"];
        $this->setToParse(trim($matcher[2]));
    }

    public function setParamsFromLastMatcher($toParse){
        preg_match(self::$lastPattern, $toParse, $matcher);
        $this->toParse = trim($matcher[2]);
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

class abstractResolveSeparatedValue extends abstractResolveValues_jcb {
    public static $firstPattern = '/^(.*)$/';
    public static $lastPattern = '/^[,](.*)/';
    protected $resolver;

    protected function initClass($className, $comaSep=",", $firstPattern='/^(.*)$/'){
       self::$lastPattern = "/^[$comaSep](.*)/";
       parent::initClass($className);
    }

    public function parse(){
        $matcher = [];
        $this->resolver = self::resolveInstance($this->getToParse());
        $this->resolver->parse();
        $exit = call_user_func([$this->resolver->getClassName(), 'lastMatch'], $this->resolver->getClassName(), $this->toParse);
        $instance->setParamsFromLastMatcher($this->toParse);
    }

    public function setParamsFromFirstMatcher($toParse){
        preg_match(self::$firstPattern, $toParse, $matcher);
        $this->toParse = trim($matcher[1]);
    }

    public function setParamsFromLastMatcher($toParse){
        preg_match(self::$lastPattern, $toParse, $matcher);
        $this->toParse = trim($matcher[1]);
    }

    public function getValue() {
        return $this->resolver->getValue();
    }

}

class rslvResolveObjectField_jcb extends abstractResolveSeparatedValue {
    public static $firstPattern = ",";
    public static $lastPattern = '/^(\w+)(\:)(.*)$/';
    protected $key;

    public function setParamsFromFirstMatcher($toParse){
        preg_match(self::$firstPattern, $toParse, $matcher);
        $this->initClass("rslvResolveObjectField_jcb");
        $this->key = $matcher[1];
        $this->setToParse($matcher[3]);
    }

    public function getKey() {
        return $this->key;
    }

    public function setKey($v){
        $this->key = $v;
    }
}

class rslvResolveArrayItem_jcb extends abstractResolveSeparatedValue {
    public static $firstPattern = NULL;
    public static $lastPattern = NULL;

    public function setParamsFromFirstMatcher($toParse){
        $this->initClass("rslvResolveArrayItem_jcb");
        parent::setParamsFromFirstMatcher($toParse);
    }
}



class rslvResolveLiteral_jcb extends abstractResolveValues_jcb {
    public static $firstPattern = '/^(?>,)?((?:\d+\.?\d*)|(?:[Tt][Rr][Uu][Ee])|(?:[Ff][Aa][Ll][Ss][Ee]))(.*)$/';
    public static $lastPattern = '/^(.*)$/';
    protected $value;
    protected $rawvalue;

    public function setParamsFromFirstMatcher($toParse){
        preg_match(self::$firstPattern, $toParse, $matcher);
        $this->initClass("rslvResolveLiteral_jcb");
        $this->rawvalue =  trim($matcher[1]);
        $this->setToParse(trim($matcher[2]));
    }

    public function setParamsFromLastMatcher($toParse){
        preg_match(self::$lastPattern, $toParse, $matcher);
        $this->toParse = trim($matcher[1]);
    }

    public function parse(){
        if(preg_match("/^\d+\.\d+$/", $this->rawvalue)){
            $this->value = (float)$this->rawvalue;
        } else if(preg_match("/^\d+$/", $this->rawvalue)){
             $this->value = (float)$this->rawvalue;
        }else{
            $this->value = filter_var( $this->rawvalue, FILTER_VALIDATE_BOOLEAN);
        }
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($v){
        $this->value = $v;
    }
}

class rslvResolveString_jcb extends rslvResolveLiteral_jcb {
    public static $firstPattern = '/^(".*?[^\\\\]")(.*)$/';
    public static $lastPattern = '/^(.*)$/';

    public function setParamsFromFirstMatcher($toParse){
        preg_match(self::$firstPattern, $toParse, $matcher);
        $this->initClass("rslvResolveString_jcb");
        $this->rawvalue = trim($matcher[1]);
        $this->setToParse(trim($matcher[2]));
    }

    public function setParamsFromLastMatcher($toParse){
        preg_match(self::$lastPattern, $toParse, $matcher);
        $this->toParse = trim($matcher[1]);
    }

    public function parse(){
        $this->value = substr($this->rawvalue,1,-1);
    }

}

