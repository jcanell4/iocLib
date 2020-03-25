<?php
/**
 * CalculateDateFromFile: retorna la fecha del fichero_continguts del proyecto
 * @culpable rafa
 */
class CalculateDateFromFile extends CalculateWithProjectId {

    public function calculate($data) {
        $file = wikiFN("{$this->projectId}:$data");
        $ret = date('d/m/Y', filemtime($file));
        return $ret;
    }

}
