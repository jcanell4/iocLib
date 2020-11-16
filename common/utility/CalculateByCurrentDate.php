<?php
/**
 * CalculateByCurrentDate: retorna la fecha del fichero_continguts del proyecto
 * @culpable josep
 */
class CalculateByCurrentDate extends AbstractCalculate {

    public function calculate($data) {
        $date = $this->getParamValue($data);
        $ret = date($date);
        return $ret;
    }

}
