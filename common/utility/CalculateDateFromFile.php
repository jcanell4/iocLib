<?php
/**
 * CalculateDateFromFile: retorna la fecha del fichero_continguts del proyecto
 * @culpable rafa
 */
class CalculateDateFromFile extends CalculateWithProjectId {

    public function calculate($data) {
        $projectId = $this->getProjectId();
        $nsFile = $this->getParamValue($data);
        $file = wikiFN("$projectId:$nsFile");
        $ret = date('d/m/Y', filemtime($file));
        return $ret;
    }

}
