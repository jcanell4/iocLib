<?php
/**
 * CommonUpgrader: Colección de funciones de transformación, para los datos de proyecto,
 *                 entre estructuras de distintas versiones
 * @culpable rafael
 */
if (!defined("DOKU_INC")) die();

class CommonUpgrader {
    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    //                    Actualización de nombres de campo del formulario
    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    /**
     * Modifica el nombre de un campo perteneciente a una tabla (multirregistro)
     * (modifica el nombre de una clave del array de datos del proyecto)
     * @param array $data : array de datos del proyecto (del archivo mdprojects/.../.../*.mdpr)
     * @param array $u0 : ruta completa (del array multinivel) de la clave original (versión 0)
     * @param array $u1 : ruta completa (del array multinivel) de la nueva clave (versión 1)
     * @return array de datos con los nombres de las claves $u0 cambiados a $u1
     */
    public function changeFieldNameInArray($data, $u0, $u1) {
        $data = $this->changeFieldName($data, $u0[0], $u1[0]);
        for ($i=0; $i<count($u0)-1; $i++) {
            $data[$u1[$i]] = $this->changeFieldNameInMultiRow($data[$u1[$i]], $u0[$i+1], $u1[$i+1]);
        }
        return $data ;
    }

    /**
     * Modifica el nombre de una clave de un array y retorna el array con la clave renombrada
     * @param array $data : array de datos
     * @param string $u0 : nombre de clave original
     * @param string $u1 : nuevo nombre de clave
     * @return array de datos con el nombre de la clave $u0 cambiado a $u1
     */
    public function changeFieldName($data, $u0, $u1) {
        $dataChanged = array();
        foreach ($data as $key => $value) {
            if ($key === $u0) {
                $dataChanged[$u1] = $value;
            }else {
                $dataChanged[$key] = $value;
            }
        }
        return $dataChanged;
    }

    public function changeFieldNameInMultiRow($rama, $u0, $u1) {
        $rama = (is_array($rama)) ? $rama : json_decode($rama, TRUE);
        if (is_array($rama[0])) { //es un conjunto de filas de una tabla
            for ($j=0; $j<count($rama); $j++) {
                $ret = $this->changeFieldName($rama[$j], $u0, $u1);
                $rama[$j] = json_encode($ret);
            }
        }else {
            $rama = $this->changeFieldName($rama, $u0, $u1);
        }
        return json_encode($rama);
    }

    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    //                              Actualización de plantillas
    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    /**
     * Aplica una nueva plantilla a un documento creado con una plantilla antigua
     * NOTA: las 2 plantillas y el documento deben tener el mismo número de fragmentos [##TODO
     *       Se equiparan los frangmentos por su número de orden de aparición, independientemente de su contenido
     * @param string $t0 : texto de la plantilla original
     * @param string $t1 : texto de la nueva plantilla
     * @param string $doc : texto del documento de usuario (basado en la plantilla orginal)
     * @return string transformado
     */
    public function comparaTemplates($t0, $t1, $doc) {
        $st0 = preg_split('/(\[##TODO.*##\])/', $t0, -1, PREG_SPLIT_DELIM_CAPTURE);
        $st1 = preg_split('/(\[##TODO.*##\])/', $t1, -1, PREG_SPLIT_DELIM_CAPTURE);

        for ($i=0; $i<count($st0); $i++) {
            $st0[$i] = "/".preg_quote($st0[$i], '/')."/";
        }

        $ret = preg_replace($st0, $st1, $doc);
        return $ret;
    }

    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    //                                  PROVES
    // ::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
    private function bajaSube($rama, $u0, $i) {
        $dato = json_decode($rama, TRUE);
        if ($i < count($u0)-1) {
            $dato[$u0[$i]] = $this->bajaSube($dato[$u0[$i]], $u0, $i+1);
            $ret = json_encode($dato);
        }else {
            $ret = json_encode($dato);
        }
        return $ret;
    }
    /**
     * Lee el contenido de una rama de un array
     * @param array $rama : array de datos
     * @param array $u0 : ruta completa (del array multinivel) de la clave a leer
     * @param integer $i : nivel
     */
    private function readArrayBranch(&$rama, $u0, $u1, $i) {
        $rama = (is_array($rama)) ? $rama : json_decode($rama, TRUE);
        if (is_array($rama[0])) { //es un conjunto de filas de una tabla
            $rama = $rama[0];
        }
        $tmp = $rama[$u0[$i]];
        if ($i < count($u0) - 1) {
            $ret = $this->readArrayBranch($tmp, $u0, $u1, $i+1);
        }else {
            unset($rama[$u0[$i]]);
            $rama[$u1[$i]] = $tmp;
            $ret = json_encode($rama);
         }
        return $ret;
    }

    /**
     * Lee el contenido de una rama de un array
     * @param array $rama : array de datos
     * @param array $u0 : ruta completa (del array multinivel) de la clave a leer
     * @param integer $i : nivel
     */
    private function readArrayBranchMultiRow(&$rama, $u0, $u1, $i) {
        $rama = (is_array($rama)) ? $rama : json_decode($rama, TRUE);
        if (is_array($rama[0])) { //es un conjunto de filas de una tabla
            for ($j=0; $j<count($rama); $j++) {
                $ret = $this->readArrayBranchMultiRow($rama[$j], $u0, $u1, $i);
            }
        }else {
            $tmp = $rama[$u0[$i]];
            unset($rama[$u0[$i]]);
            $rama[$u1[$i]] = $tmp;
            if ($i < count($u0)-1) {
                $ret = $this->readArrayBranchMultiRow($tmp, $u0, $u1, $i+1);
            }else {
                $ret = $rama;
            }
        }
        return $ret;
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

    //Obtiene un array (de 1 dimensión) en el que el conjunto de sus claves especifican, en orden inverso,
    //la localización de la clave buscada
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
