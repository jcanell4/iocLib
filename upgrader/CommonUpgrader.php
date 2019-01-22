<?php
/**
 * CommonUpgrader: Colección de funciones de transformación, para los datos de proyecto,
 *                 entre estructuras de distintas versiones
 * @author rafael
 */
if (!defined("DOKU_INC")) die();

class CommonUpgrader {

    /**
     * Modifica el nombre de un campo (modifica el nombre de una clave del array de datos del proyecto)
     * @param dataProject : array de datos del proyecto (del archivo mdprojects/.../.../*.mdpr)
     * @param name_0 : nombre de clave original (versión 0)
     * @param name_1 : nuevo nombre de clave (versión 1)
     */
    public function changeFieldNameArray($data, $name_0, $name_1) {
        $items0 = (is_array($name_0)) ? $name_0 : explode(":", $name_0);
        $items1 = (is_array($name_1)) ? $name_1 : explode(":", $name_1);
        $rama = array();
        for ($i=0; $i<=count($items0); $i++) {
            $rama = $data[$items0[$i]];
            $a = json_decode($rama, TRUE);
            if (is_array($a) && isset($items0[$i+1])) {
                if (is_array($a[0])) {
                    for ($j=0; $j<count($a); $j++) {
                        $ret[$j] = $this->changeFieldNameArray($a[$j], $items0[$i+1], $items1[$i+1]);
                    }
                }else{
                    $ret = $this->changeFieldNameArray($a, $items0[$i+1], $items1[$i+1]);
                }
            }else {
                $data[$items1[$i]] = $rama;
                return $data;
            }
        }
//        for ($i=0; $i<=count($items0); $i++) {
//            unset($dataProject[$items0[$i]]);
//        }
        return $data;
    }

    /**
     * Modifica el nombre de una clave de un array y retorna el array con la clave renombrada
     * @param arrayData : array de datos
     * @param name_0 : nombre de clave original
     * @param name_1 : nuevo nombre de clave
     */
    public function changeFieldName($arrayData, $name_0, $name_1) {
        $dataChanged = array();
        foreach ($arrayData as $key => $value) {
            if ($key === $name_0) {
                $dataChanged[$name_1] = $value;
            }else {
                $dataChanged[$key] = $value;
            }
        }
        return $dataChanged;
    }

    /**
     * Modifica el nombre de un campo (modifica el nombre de una clave del array de datos del proyecto)
     * @param dataProject : array de datos del proyecto (del archivo mdprojects/.../.../*.mdpr)
     * @param name_0 : nombre de clave original (versión 0)
     * @param name_1 : nuevo nombre de clave (versión 1)
     */
    public function fieldNameArrayChanged($dataProject, $name_0, $name_1) {
        $items0 = explode(":", $name_0);
        $items1 = explode(":", $name_1);
        $old_value = $dataProject;
        for ($i=0; $i<=count($items0); $i++) {
            if ($old_value[$items0[$i]]) {
                $a = json_decode($old_value[$items0[$i]], TRUE);
                $old_value = &$old_value[$items0[$i]];
                if ($old_value) {
                    //unset($dataProject[$items0[$i]]);
                    $dataProject[$items1[$i]] = $old_value; //insert
                }
            }
        }
//        for ($i=0; $i<=count($items0); $i++) {
//            unset($dataProject[$items0[$i]]);
//        }
        return $dataProject;
    }

    //Transforma el objeto en un array puro
    public function JsonToArray($obj) {
        $arr = array();
        foreach ($obj as $k => $v) {
            $a = json_decode($v, TRUE);
            $arr[$k] = ($a) ? $a : $v;
        }
        return $arr;
    }

    public function buildArrayFromStringTokenized(&$data, $path) {
        $temp = &$data;
        foreach(explode(":", $path) as $key) {
            $temp = &$temp[$key];
        }
        $temp = "";
    }

    //Obtiene el valor de una subclave del array pasada como una ruta: un string partido en tokens
    public function getValueArrayFromIndexString($data, $path) {
        $temp = $data;
        foreach(explode(":", $path) as $ndx) {
            $temp = isset($temp[$ndx]) ? $temp[$ndx] : null;
        }
        return $temp;
    }

    //Obtiene un array (de 1 dimensión) en el que el conjunto de sus claves especifican, en orden inverso, la localización de la clave buscada
    public function getKeyPathArray($arr, $lookup) {
        if (array_key_exists($lookup, $arr)) {
            return array($lookup);
        }else {
            foreach ($arr as $key => $subarr){
                if (is_array($subarr)) {
                    $ret = $this->getKeyPathArray($subarr, $lookup);
                    if ($ret) {
                        $ret[] = $key;
                        return $ret;
                    }
                }
            }
        }
        return null;
    }

}
