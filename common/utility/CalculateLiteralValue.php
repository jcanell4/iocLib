<?php
/**
 * @class CalculateLiteralValue
 *
 * Aquesta clase retorna com a calculat el valor indicat al camp "value".
 *
 * @author xaviergaro.dev@gamail.com
 */
class CalculateLiteralValue extends AbstractCalculate {
    const FIELD_PARAM = "value";

    public function calculate($data) {
        $ret = $this->getParamValue($data[self::FIELD_PARAM]);
        return $ret;
    }
}
