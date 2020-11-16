<?php
/**
 * CalculateWithPersistence
 */
abstract class CalculateWithPersistence extends AbstractCalculate implements ICalculateWithPersistence {

     public function __construct() {
         $this->addCalculatorTypeData(self::WITH_PERSISTENCE_TYPE);
         $this->setCalculatorTypeToInitParam(self::WITH_PERSISTENCE_TYPE, self::PERSISTENCE_VAR);
     }

    function getPersistence(){
        return $this->getInitParams(self::WITH_PERSISTENCE_TYPE);
    }
}
