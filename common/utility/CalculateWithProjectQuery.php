<?php
/**
 * CalculateWithPersistence
 */
abstract class CalculateWithPersistence extends AbstractCalculate implements ICalculateWithPersistence {

    protected $persistence;

    function init($value) {
        $this->persistence = $value;
    }

    function getCalculatorTypeData(){
        return [self::WITH_PERSISTENCE_TYPE];
    }

    function getPersistence(){
        return $this->persistence;
    }
    
}
