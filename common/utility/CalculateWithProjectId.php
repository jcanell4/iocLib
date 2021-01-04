<?php
/**
 * CalculateWithProjectId
 */
 abstract class CalculateWithProjectId extends AbstractCalculate implements ICalculateWithProjectId {
     
    public function __construct() {
        $this->addCalculatorTypeData(self::WITH_PROJECT_ID_TYPE);
        $this->setCalculatorTypeToInitParam(self::WITH_PROJECT_ID_TYPE, self::PROJECT_ID_VAR);
    }

    function getProjectId(){
        return $this->getVariable(self::PROJECT_ID_VAR);
    }
    
}
