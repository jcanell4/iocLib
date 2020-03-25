<?php
/**
 * CalculateWithValue
 * @culpable rafa
 */
require_once(__DIR__ . "/AbstractCalculate.php");

 interface ICalculateWithPersistence{
    const WITH_PERSISTENCE_TYPE="with_persistence";
    
    function init($value);
    
    function getPersistence();
}
