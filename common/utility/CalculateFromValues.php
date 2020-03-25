<?php
/**
 * CalculateWithValue
 * @culpable rafa
 */
require_once(__DIR__ . "/AbstractCalculate.php");

 abstract class CalculateFromValues extends AbstractCalculate implements ICalculateFromValues{
   protected $values;

    function init($value) {
        $this->values = $value;
    }
    
    function getCalculatorTypeData(){
        return [self::FROM_VALUES_TYPE];
    }
    
    function getValues(){
        return $this->values;
    }
}
