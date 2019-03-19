<?php
/**
 * CalculateWithValue
 * @culpable rafa
 */
require_once(__DIR__ . "/AbstractCalculate.php");

abstract class CalculateWithValue extends AbstractCalculate {
    protected $ns;

    function init($ns) {
        $this->ns = $ns;
    }
}
