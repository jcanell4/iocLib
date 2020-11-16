<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CalculateWithPersistenceAndValues
 *
 * @author josep
 */
abstract class CalculateWithPersistenceAndValues  extends CalculateWithPersistence implements ICalculateFromValues{
    const DEFAULT_VALUE_FROM_FIELDS_TYPE = "defaultValueFromFields";
    const DEFAULT_VALUE_FROM_VALUE_TYPE = "defaultValueFromValue";
    const TYPE_PARAM = "type";
    
    public function __construct() {
        parent::__construct();
        $this->addCalculatorTypeData(self::FROM_VALUES_TYPE);
        $this->setCalculatorTypeToInitParam(self::FROM_VALUES_TYPE, self::VALUES_VAR);
    }

    public function getValues() {
        return $this->getInitParams(self::FROM_VALUES_TYPE);
    }
}
