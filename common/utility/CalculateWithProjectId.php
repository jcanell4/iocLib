<?php
/**
 * CalculateWithProjectId
 */
 abstract class CalculateWithProjectId extends AbstractCalculate implements ICalculateWithProjectId {

    protected $projectId;

    function init($value) {
        $this->projectId = $value;
    }

    function getCalculatorTypeData(){
        return [self::WITH_PROJECT_ID_TYPE];
    }

    function getProjectId(){
        return $this->projectId;
    }
    
}
