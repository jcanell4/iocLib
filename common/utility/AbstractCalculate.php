<?php
/**
 * AbstractCalculate
 * @culpable rafa
 */
abstract class AbstractCalculate implements ICalculate{
    const DEFAULT_VALUE_PARAM = "defaultValue";
    const DEFAULT_TYPE="default";
    private $typeDatas=array();
    private $variables=array();
    private $typeDatasToInitParamsMap=array();
    private $initDefaultValue=NULL;
    

    public function init($values, $calculatorType, $defaultValue=NULL){
        $this->variables[$this->typeDatasToInitParamsMap[$calculatorType]]=$values;
        $this->initDefaultValue = $defaultValue;
    }

    protected function getInitParams($calculatorType){
        return $this->variables[$this->typeDatasToInitParamsMap[$calculatorType]];
    }

    public function getCalculatorTypeData(){
        return $this->typeDatas;
    }

    public function addCalculatorTypeData($typeData){
        $this->typeDatas[] = $typeData;
    }

    public function setCalculatorTypeToInitParam($typeData, $initParam){
        $this->typeDatasToInitParamsMap[$typeData] = $initParam;
    }

    public function isCalculatorOfTypeData($typeData){
        return in_array($typeData, $this->getCalculatorTypeData());
    }

    public function getDefaultValue($data, $error=TRUE){
        if(isset($data[self::DEFAULT_VALUE_PARAM])){
            $ret = $this->getParamValue($data[self::DEFAULT_VALUE_PARAM]);
        } elseif($this->initDefaultValue!=NULL){
            $ret = $this->initDefaultValue;
        } elseif(!$error){
            $ret = FALSE;
        } else{
            throw new Exception("Error: No no hi ha definit cap valor per defecte al configMain");
        }
        return $ret;
    }

    public abstract function calculate($data);

    protected function setVariable($name, $value){
        $this->variables[$name] = $value;
        return $value;
    }

    protected function getVariable($name){
        return $this->variables[$name];
    }

    protected function unsetVariable($name){
        unset($this->variables[$name]);
    }


    protected function getParamValue($value){
       if(is_array($value) && isset($value["_type_"])){
           $ret = $this->getTypedValue($value);
       }elseif(is_string($value) && $value[0]=='$'){
           $name = substr($value, 1);
           $ret = $this->getValueFromVariable($name);
       }else{
           $ret = $value;
       }
       return $ret;
    }

    private function getTypedValue($value){
        switch($value["_type_"]){
            case "var":
                $name = $this->getParamValue($value["name"]);
                $ret = $this->getValueFromVariable($name);
                break;
            case "field":
                $values = $this->getParamValue($value["values"]);
                $field = $this->getParamValue($value["name"]);
                $defaultValue = $this->getParamValue($value["defaultValue"]);
                $ret = $this->getValueFieldFromValues($values, $field, $defaultValue);
                break;
            case "calculatedFromValues":
                $values =$this->getParamValue($value["values"]);
                $class = $this->getParamValue($value["class"]);
                $data = $this->getParamValue($value["data"]);
                if(isset($value["variables"])){
                    $variables =$value["variables"];
                }else{
                    $variables = FALSE;
                }
                $ret = $this->getCalculatedValueFromValues($values, $class, $data, $variables);
                break;
            case "operation":
                $values =$this->getParamValue($value["values"]);
                $operation =$this->getParamValue($value["operation"]);
                $ret = $this->getOperationResult($values, $operation);
                break;
            case "literal":
                $ret = $this->getParamValue($value["value"]);
            default :
                throw new Exception("No hi ha cap tipus estructurat en el sistema \"Calculate\" anomenat \"{$value["_type_"]}\".");
        }
        return $ret;
    }

    /* FUNCIONS QUE VENEN DES DE PARÀMETRES i es criden a través de getParamValue*/

    protected function getValueFromVariable($name){
        return $this->variables[$name];
    }

    protected function getValueFieldFromValues($values, $field, $defaultValue=NULL){
        $ret = $values[$field];

        if (!isset($ret)) {
            $components = explode("#", $field);
            $ret = $values;
            foreach ($components as $sfield) {
                if($ret){
                    $ret = $ret[$sfield];
                }
            }
        }

        if (!isset($ret) && $defaultValue!==NULL){
            $ret = $defaultValue;
        }elseif(!isset($ret)){
            if($this->initDefaultValue!==NULL){
                $ret = $this->initDefaultValue;
            }else{
                throw new Exception("Error: No s'ha trobat {$field} definit a configMain");
            }
        }
        return $ret;
    }

    protected function getCalculatedValueFromValues($values, $class, $data, $variables=FALSE){
        $calculator = new $class;
        $calculator->init($values, ICalculateFromValues::FROM_VALUES_TYPE);
        if($variables){
            foreach ($variables as $key => $value) {
                $v = $this->getParamValue($value);
                $calculator->setVariable($key, $v);
            }
        }
        return $calculator->calculate($data);
    }

    protected function getOperationResult($values, $operation){
        $ret = true;
        switch ($operation){
            case "=":
            case "==":
                $value1 = $this->getParamValue($values[0]);
                $value2 = $this->getParamValue($values[1]);
                $ret = $value1 == $value2;
                break;
            case "<":
                $value1 = $this->getParamValue($values[0]);
                $value2 = $this->getParamValue($values[1]);
                $ret = $value1 < $value2;
                break;
            case "<=":
                $value1 = $this->getParamValue($values[0]);
                $value2 = $this->getParamValue($values[1]);
                $ret = $value1 <= $value2;
                break;
            case ">":
                $value1 = $this->getParamValue($values[0]);
                $value2 = $this->getParamValue($values[1]);
                $ret = $value1 > $value2;
                break;
            case ">=":
                $value1 = $this->getParamValue($values[0]);
                $value2 = $this->getParamValue($values[1]);
                $ret = $value1 >= $value2;
                break;
            case "!=":
                $value1 = $this->getParamValue($values[0]);
                $value2 = $this->getParamValue($values[1]);
                $ret = $value1 != $value2;
                break;
            case "!":
                $value1 = $this->getParamValue($values[0]);
                $ret = !$value1;
                break;
            case "+":
                $ret = 0;
                foreach ($values as $value) {
                    $value1 = $this->getParamValue($value);
                    $ret += $value1;
                }
                break;
            case "-":
                $ret = 0;
                foreach ($values as $value) {
                    $value1 = $this->getParamValue($value);
                    $ret -= $value1;
                }
                break;

            case "*":
                $ret = 0;
                foreach ($values as $value) {
                    $value1 = $this->getParamValue($value);
                    $ret *= $value1;
                }
                break;
            case "/":
                $ret = 0;
                foreach ($values as $value) {
                    $value1 = $this->getParamValue($value);
                    $ret /= $value1;
                }
                break;
            case ".":
            case "concat":
                $ret = "";
                foreach ($values as $value) {
                    $value1 = $this->getParamValue($value);
                    $ret .= $value1;
                }
                break;
            case "mergeArray":
                $ret = array();
                foreach ($values as $value) {
                    $value1 = $this->getParamValue($value);
                    $ret = array_merge($ret, $value1);
                }
                break;

        }
        return $ret;
    }

    protected function existValueInArray($values, $field, $valueToSearch, $toReturn=FALSE){
        $ret=FALSE;

        $array = $this->setVariable(ICalculate::ARRAY_VALUE_VAR, $this->castToArray($this->getValueFieldFromValues($values, $field)));

        if($toReturn){
            $ret = $this->getParamValue($toReturn["ifFalse"]);
            $RetIfTrue = $this->getParamValue($toReturn["ifTrue"]);
        }else{
            $RetIfTrue = TRUE;
        }
        $pos=0;
        foreach ($array as $element) {
            $this->setVariable(ICalculate::ARRAY_ELEMENT_VAR, $element);
            if($element === $valueToSearch){
                $ret = $RetIfTrue;
                break;
            }
            $pos++;
        }
        return $ret;
    }

    protected function existValueInArrayObject($values, $field, $fieldToSearch, $valueToSearch, $toReturn=FALSE){
        $ret=FALSE;

        $array = $this->setVariable(ICalculate::ARRAY_OBJECT_VALUE_VAR, $this->castToArray($this->getValueFieldFromValues($values, $field)));

        if($toReturn){
            $ret = $this->getParamValue($toReturn["ifFalse"]);
            $RetIfTrue = $this->getParamValue($toReturn["ifTrue"]);
        }else{
            $RetIfTrue = TRUE;
        }
        $pos=0;
        foreach ($array as $element) {
            $this->setVariable(ICalculate::ROW_VALUE_VAR, $element);
            if($element[$fieldToSearch] === $valueToSearch){
                $ret = $RetIfTrue;
                break;
            }
            $pos++;
        }
        return $ret;
    }

    protected function castToArray($var, $ThrowException=TRUE){
        $ret = NULL;
        if (is_string($var)){
            $ret = json_decode($var, TRUE);
        }elseif(is_array($var)){
            $ret = $var;
        }elseif($ThrowException) {
            throw new Exception("'$var' no es pot transformar a un tipus ARRAY");
        }
        return $ret;
    }
}
