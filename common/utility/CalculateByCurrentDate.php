<?php
/**
 * CalculateByCurrentDate: retorna la fecha del fichero_continguts del proyecto
 * @culpable josep
 */
class CalculateByCurrentDate extends AbstractCalculate {

    public function calculate($data) {
        $ret = date($data);
        return $ret;
    }

}
