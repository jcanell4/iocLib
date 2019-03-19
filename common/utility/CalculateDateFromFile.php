<?php
/**
 * CalculateDateFromFile: retorna la fecha del fichero_continguts del proyecto
 * @culpable rafa
 */
class CalculateDateFromFile extends CalculateWithValue {

    public function calculate($data) {
        $file = wikiFN("{$this->ns}:$data");
        $ret = date('d/m/Y', filemtime($file));
        return $ret;
    }

}
