<?php
/**
 * CalculateDateFromFile: retorna la fecha del fichero_continguts del proyecto
 * @culpable josep
 */
require_once(__DIR__ . "/AbstractCalculate.php");

class CalculateByCurrentDate extends AbstractCalculate {

    public function calculate($data) {
        $ret = date($data);
        return $ret;
    }
}
