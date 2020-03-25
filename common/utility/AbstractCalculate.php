<?php
/**
 * AbstractCalculate
 * @culpable rafa
 */
abstract class AbstractCalculate {
    const DEFAULT_TYPE="default";
    private $typeDatas=array();

    public function getCalculatorTypeData(){
        return $this->typeDatas;
    }

    public function addCalculatorTypeData($typeData){
        $this->typeDatas[] = $typeData;
    }
    
    public function isCalculatorOfTypeData($typeData){
        return in_array($typeData, $this->getCalculatorTypeData());
    }
    
    public abstract function calculate($data);

}
