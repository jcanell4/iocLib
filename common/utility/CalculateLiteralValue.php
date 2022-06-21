<?php
/**
 * Description of CalculateSimpleValueFromExternaField.
 * 
 * @author xaviergaro.dev@gamail.com
 */
class CalculateLiteralValue extends AbstractCalculate {
//    const FIELD_PARAM = "field";
    const FIELD_PARAM = "value";

    public function calculate($data) {
//        $values = $this->getValues();
//        if($values){
            $ret = $this->getParamValue($data[self::FIELD_PARAM]);
//            $field = $this->getParamValue($data[self::FIELD_PARAM]);
//            $ret = $this->getValueFieldFromValues($values, $field);
//        }else{
//            $ret = $this->getDefaultValue($data);
//        }

        return $ret;
    }
}
