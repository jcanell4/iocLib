<?php
/**
 * AbstractCalculate
 * @culpable rafa
 */
abstract class AbstractCalculate {
    public function getCalculatorTypeData(){
        return "";
    }
    
    public abstract function calculate($data);

}
