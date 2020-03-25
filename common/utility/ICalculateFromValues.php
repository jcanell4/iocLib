<?php
/**
 * CalculateWithValue
 * @culpable rafa
 */
require_once(__DIR__ . "/AbstractCalculate.php");

 interface ICalculateFromValues{
    const FROM_VALUES_TYPE="from_values";
    
    function init($values);
    
    function getValues();
}
