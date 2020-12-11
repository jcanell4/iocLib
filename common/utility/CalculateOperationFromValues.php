<?php

class CalculateOperationFromValues extends CalculateFromValues{
    const OPERATION_PARAM = "operation";
    const OPERATION_VALUES_PARAM = "operationValues";
    const RETURN_VALUES_PARAM = "returnValues";
    
    //put your code here
    public function calculate($data) {
        $operationValues = $this->getParamValue($data[self::OPERATION_VALUES_PARAM]);
        $operator = $this->getParamValue($data[self::OPERATION_PARAM]);
        $ret  = $this->getOperationResult($operationValues, $operator);
        return $ret;
    }
}
