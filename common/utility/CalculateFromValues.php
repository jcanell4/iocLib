<?php
/**
 * CalculateFromValues
 */
abstract class CalculateFromValues extends AbstractCalculate implements ICalculateFromValues {

    function __construct() {
        $this->addCalculatorTypeData(self::FROM_VALUES_TYPE);
        $this->setCalculatorTypeToInitParam(self::FROM_VALUES_TYPE, self::VALUES_VAR);
    }
    
    function getValues(){
        return $this->getVariable(ICalculateFromValues::VALUES_VAR);
    }

}
