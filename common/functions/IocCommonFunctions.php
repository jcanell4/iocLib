<?php

class IocCommonFunctions
{
    public static function extractComaSeparatedValues($text)
    {
        $array = [];
        $patternParams =  '/ ? ?(\[.*?\])|(".*?")| ?(?:,)| ?(\d+\.?\d*?)| ?(.*),| ?(.*)|/m';
        if (preg_match_all($patternParams, $text, $matchParams,  PREG_SET_ORDER)) {

            // El darrer element sempre és buit
            for ($i =0; $i<count($matchParams)-1; $i++) {
                $value = $matchParams[$i][count($matchParams[$i])-1];
                if (count($matchParams[$i])>1 ) {
                    $array[] = $value;
                }
            }
        }

        return $array;
    }

    public static function COUNTINARRAY($array, $fields, $values = NULL, $strict = false)
    {
        if ($values == NULL) {
            $values = $fields;
            $fields = NULL;
        }
        $cont = 0;
        foreach ($array as $item) {
            if (self::_arrayFilter($item, $values, $fields, $strict)) {
                $cont++;
            }
        }
        return $cont;
    }


    public static function IS_STR_EMPTY($text = "")
    {
        return empty($text) ? "true" : "false";
    }

    public static function YEAR($date = NULL)
    {
        if ($date == NULL) {
            $ret = date("Y");
        } else {
            date("Y", strtotime(str_replace('/', '-', $date)));
        }
        return $ret;
    }

    public static function DATE($date = NULL, $sep = "-")
    {
        if (!is_string($date)) {
            return "[ERROR! paràmetres incorrectes DATE($date, $sep)]"; //TODO: internacionalitzar
        }
        return date("d" . $sep . "m" . $sep . "Y", strtotime(str_replace('/', '-', $date)));
    }

    public static function LONG_DATE($date = NULL, $includeDay = FALSE)
    {
        if (!is_string($date)) {
            return "[ERROR! paràmetres incorrectes LONG_DATE($date, $includeDay)]"; //TODO: internacionalitzar
        }
        $format = '';

        if ($includeDay) {
            //$format .= "l, ";
            $format .= "%A, ";
        }

        setlocale(LC_TIME, "ca_ES.utf8");
        $format .= "%e de %B de %G";

        return strftime($format, strtotime($date));
        //return date($format, strtotime(str_replace('/', '-', $date)));
    }

    public static function SUM_DATE($date, $days, $months = 0, $years = 0, $sep = "-")
    {
        if (!is_string($date) || !is_numeric($days) || !is_numeric($months) || !is_numeric($years)) {
            return "[ERROR! paràmetres incorrectes SUM_DATE($days, $months, $years)]"; //TODO: internacionalitzar
        }

        $newDate = $date;

        if ($days > 0) {
            $calculated = strtotime("+" . $days . " day", strtotime($date));
            $newDate = date("Y" . $sep . "m" . $sep . "d", $calculated);
        }

        if ($months > 0) {

            $calculated = strtotime("+" . $months . " month", strtotime($newDate));
            $newDate = date("Y" . $sep . "m" . $sep . "d", $calculated);
        }

        if ($years > 0) {
            $calculated = strtotime("+" . $years . " year", strtotime($newDate));
            $newDate = date("Y" . $sep . "m" . $sep . "d", $calculated);
        }

        return $newDate;
    }

    public static function IN_ARRAY($value, $array)
    {
        $ret = in_array($value, $array);
        return $ret;
    }

    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    public static function SEARCH_ROW($toSearch, $array, $column, $default = false)
    {
        $key = static::SEARCH_KEY($toSearch, $array, $column);
        if ($key === false || $key === "false") {
            if ($default === false) {
                $ret = $default;
            } else {
                $ret = "null";
            }
        } else {
            $ret = $array[$key];


        }
        return $ret;
//        return self::_normalizeValue($ret);
    }

    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    //[JOSEP]TODO: Cal canviar el nom de la funció per SEARCH_ROW per precisar millor la seva funcionalitat.
    // Per fer el canvi caldrà crear un nou procediment upgrade en tots els projectes.
    /*
     * @Deprecated use SEARCH_ROW.
     */
    public static function SEARCH_VALUE($toSearch, $array, $column)
    {
        return static::SEARCH_ROW($toSearch, $array, $column);
    }

    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    public static function SEARCH_KEY($toSearch, $array, $column = NULL)
    {
        if ($column != NULL) {
            if (is_array($column) && is_array($toSearch)) {
                $key = false;
                for ($i = 0; $key === false && $i < count($array); $i++) {
                    $isOk = true;
                    for ($j = 0; $j < count($column); $j++) {
                        $isOk = $isOk && $array[$i][$column[$j]] == $toSearch[$j];
                    }
                    if ($isOk) {
                        $key = $i;
                    }
                }
            } elseif (is_array($column)) {
                $key = false;
                for ($i = 0; $key === false && $i < count($array); $i++) {
                    $isOk = true;
                    foreach ($column as $field) {
                        $isOk = $isOk && $array[$i][$field] == $toSearch;
                    }
                    if ($isOk) {
                        $key = $i;
                    }
                }
            } elseif (is_array($toSearch)) {
                $key = false;
                for ($i = 0; $key === false && $i < count($array); $i++) {
                    $isOk = true;
                    foreach ($toSearch as $value) {
                        $isOk = $isOk && $array[$i][$column] == $value;
                    }
                    if ($isOk) {
                        $key = $i;
                    }
                }
            } else {
                $key = array_search($toSearch, array_column($array, $column));
            }
        } else {
            $key = array_search($toSearch, $array);
        }
        return $key;
//        return self::_normalizeValue($key);
    }

    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    public static function ARRAY_GET_VALUE($key, $array, $defaultValue = FALSE)
    {
        if ($key === null || !is_array($array)) {
            if ($defaultValue === false) {
                return "[ERROR! paràmetres incorrectes ARRAY_GET_VALUE($key, $array)]"; //TODO: internacionalitzar
            } else {
                return $defaultValue;
            }
        } elseif ($key < 0 || $key >= count($array)) {
            if ($defaultValue === false) {
                return "[ERROR! key fora de rang ARRAY_GET_VALUE($key, $array)]"; //TODO: internacionalitzar
            } else {
                return $defaultValue;
            }
        }
        $ret = isset($array[$key]) ? $array[$key] : $defaultValue;

        return $ret;
//        return self::_normalizeValue($ret);
    }

    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    public static function ARRAY_LENGTH($array = NULL)
    {
        if ($array === NULL) {
            return 0;
        } elseif (!is_array($array)) {
            return "[ERROR! paràmetres incorrectes ARRAY_LENGTH($array)]"; //TODO: internacionalitzar
        }
        return count($array);
    }

    public static function COUNTDISTINCT($array, $fields)
    {
        if (!is_array($array) || !is_array($fields)) {
            return "[ERROR! paràmetres incorrectes COUNTDISTINCT($array, $fields)]"; //TODO: internacionalitzar
        }

        $unique = [];

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($array as $item) {
            $aux = '';
            foreach ($fields as $field) {
                $aux .= $item[$field];
            }
            if (!in_array($aux, $unique)) {
                $unique[] = $aux;
            }
        }

        return count($unique);
    }


    public static function FIRST($array, $template)
    {
        if (!is_array($array) || !is_string($template)) {
            return "[ERROR! paràmetres incorrectes FIRST($array, $template)]"; //TODO: internacionalitzar
        }
        return static::formatItem($array[0], 'FIRST', $template);
    }

    public static function LAST($array, $template)
    {
        if (!is_array($array) || !is_string($template)) {
            return "[ERROR! paràmetres incorrectes LAST($array, $template)]"; //TODO: internacionalitzar
        }
        return static::formatItem($array[count($array) - 1], 'LAST', $template);
    }

//    private static function _normalizeValue($ret)
//    {
//        if (is_array($ret) || is_object($ret)) {
//            $ret = json_encode($ret);
//        } else if (is_bool($ret)) {
//            $ret = $ret ? "true" : "false";
////        }else if(is_string($ret)){
////            $ret = "\"$ret\"";
//        }
//        return $ret;
//    }

    private static function _arrayFilter($element, $value, $field = false, $strict = false)
    {
        if (is_array($value)) {
            if (is_array($field)) {
                $compliant = true;
                for ($i = 0; $compliant && $i < count($field); $i++) {
                    $compliant = $compliant && self::_arrayFilter($element[$field[$i]], $value[$i]);
                }
            } elseif ($field === false) {
                $compliant = in_array($element, $value, $strict);
            } else {
                $compliant = in_array($element[$field], $value, $strict);
            }
        } else {
            if (is_array($field)) {
                $compliant = true;
                for ($i = 0; $compliant && $i < count($field); $i++) {
                    $compliant = $compliant && $element[$field[$i]] == $value;
                }
            } elseif ($field === false) {
                $compliant = $strict ? $element === $value : $element == $value;
            } else {
                $compliant = $strict ? $element[$field] === $value : $element[$field] == $value;
            }
        }
        return $compliant;
    }

    private static function _compareMultiObjectFields($obj1, $obj2, $type, $fields, $pos = 0)
    {
        if ($pos >= count($fields)) {
            $ret = substr($type, 1, 1) === "=";
        } else if ($obj1[$fields[$pos]] == $obj2[$fields[$pos]]) {
            $ret = self::_compareMultiObjectFields($obj1, $obj2, $fields, $type, $pos + 1);
        } else {
            $ret = self::_compareSingleValues($obj1[$fields[$pos]], $obj2[$fields[$pos]], $type);
        }
        return $ret;
    }

    private static function _compareSingleObjectFields($obj1, $obj2, $type, $field)
    {
        return self::_compareSingleValues($obj1[$field], $obj2[$field], $type);
    }

    private static function _compareSingleValues($v1, $v2, $type)
    {
        $ret = FALSE;
        switch ($type) {
            case "<":
                $ret = $v1 < $v2;
                break;
            case ">":
                $ret = $v1 > $v2;
                break;
            case "<=":
                $ret = $v1 <= $v2;
                break;
            case ">=":
                $ret = $v1 >= $v2;
                break;
        }
        return $ret;
    }

    public static function MIN($array, $template = "MIN", $fields = NULL)
    {
        $valueFromTemplate = FALSE;
        if ($fields == NULL) { //ARRAY
            $compare = "_compareSingleValues";
            $valueFromTemplate = $template !== "MIN";
        } else if (is_array($fields)) { //OBJECT and multi field comparation
            $compare = "_compareMultiObjectFields";
        } else {  //OBJECT and single fiels comparation
            $compare = "_compareSingleObjectFields";
        }
        if (count($array) > 0) {
            $min = 0;
            for ($pos = 1; $pos < count($array); $pos++) {
                if ($valueFromTemplate) {
                    $v1 = static::formatItem($array[$pos], 'MIN', $template);
                    $v2 = static::formatItem($array[$min], 'MIN', $template);
                } else {
                    $v1 = $array[$pos];
                    $v2 = $array[$min];
                }
                if (self::{$compare}($v1, $v2, "<", $fields)) {
                    $min = $pos;
                }
            }
        } else {
            return "[ERROR! array buit]"; //TODO: internacionalitzar
        }
        return static::formatItem($array[$min], 'MIN', $template);
    }

    public static function MAX($array, $template = "MAX", $fields = NULL)
    {
        $valueFromTemplate = FALSE;
        if ($fields == NULL) { //ARRAY
            $compare = "_compareSingleValues";
            $valueFromTemplate = $template !== "MAX";
        } else if (is_array($fields)) { //OBJECT and multi field comparation
            $compare = "_compareMultiObjectFields";
        } else {  //OBJECT and single fiels comparation
            $compare = "_compareSingleObjectFields";
        }
        if (count($array) > 0) {
            $max = 0;
            for ($pos = 1; $pos < count($array); $pos++) {
                if ($valueFromTemplate) {
                    $v1 = static::formatItem($array[$pos], 'MAX', $template);
                    $v2 = static::formatItem($array[$max], 'MAX', $template);
                } else {
                    $v1 = $array[$pos];
                    $v2 = $array[$max];
                }
                if (self::{$compare}($array[$pos], $array[$max], ">", $fields)) {
                    $max = $pos;
                }
            }
        } else {
            return "[ERROR! array buit]"; //TODO: internacionalitzar
        }
        return static::formatItem($array[$max], 'MAX', $template);
    }

    public static function SUBS($value1, $value2)
    {
        if (!is_numeric($value1) || !is_numeric($value2)) {
            return "[ERROR! paràmetres incorrectes SUBS($value1, $value2)]"; //TODO: internacionalitzar
        }
        return $value1 - $value2;
    }

    public static function SUMA($value1 = "NULL", $value2 = "NULL")
    {
        if (!is_numeric($value1) || !is_numeric($value2)) {
            return "[ERROR! paràmetres incorrectes SUMA($value1, $value2)]"; //TODO: internacionalitzar
        }
        return $value1 + $value2;
    }

    public static function UPPERCASE($value1, $value2, $value3 = 0)
    {
        if (!is_numeric($value2) || !is_numeric($value3)) {
            return "[ERROR! paràmetres incorrectes UPPERCASE($value1, $value2, $value3)]"; //TODO: internacionalitzar
        }
        if ($value3 == 0) {
            $value3 = $value2;
            $value2 = 0;
        }
        $ret = strtoupper(substr($value1, $value2, $value3));
        if ($value3 < strlen($value1)) {
            $ret .= substr($value1, $value3, strlen($value1));
        }
        return $ret;
    }

    public static function LOWERCASE($value1, $value2, $value3 = 0)
    {
        if (!is_numeric($value2) || !is_numeric($value3)) {
            return "[ERROR! paràmetres incorrectes LOWERCASE($value1, $value2, $value3)]"; //TODO: internacionalitzar
        }
        if ($value3 == 0) {
            $value3 = $value2;
            $value2 = 0;
        }
        $ret = strtolower(substr($value1, $value2, $value3));
        if ($value3 < strlen($value1)) {
            $ret .= substr($value1, $value3, strlen($value1));
        }
        return $ret;
    }

    // Uppercase només pel primer caràcter
    public static function UCFIRST($value = NULL)
    {
        if (!is_string($value)) {
            return "[ERROR! paràmetres incorrectes UCFIRST($value)]"; //TODO: internacionalitzar
        }
        return ucfirst($value);
    }

    // Uppercase només pel primer caràcter
    public static function LCFIRST($value = NULL)
    {
        if (!is_string($value)) {
            return "[ERROR! paràmetres incorrectes UCFIRST($value)]"; //TODO: internacionalitzar
        }
        return lcfirst($value);
    }

    public static function STR_CONTAINS($subs = NULL, $string = NULL)
    {
        if (!is_string($subs) || !is_string($string)) {
            return "[ERROR! paràmetres incorrectes STR_CONTAINS($subs, $string)]"; //TODO: internacionalitzar
        }
        return (strpos($string, $subs) !== FALSE) ? "true" : "false";
    }

    public static function EXPLODE($delimiter = NULL, $string = NULL, $limit = false, $trim = false)
    {
        if (!is_string($delimiter) || !is_string($string)) {
            return "[ERROR! paràmetres incorrectes EXPLODE($delimiter, $string, $limit)]"; //TODO: internacionalitzar
        }

        if (!$limit || $limit === "ALL") {
            $limit = PHP_INT_MAX;
        }
        $ret = explode($delimiter, $string, $limit);
        if ($trim) {
            for ($i = 0; $i < count($ret); $i++) {
                if (is_string($trim)) {
                    $ret[$i] = trim($ret[$i], $trim);
                } else {
                    $ret[$i] = trim($ret[$i]);
                }
            }
        }
//        $ret = self::_normalizeValue($ret);
        return $ret;
    }

    public static function STR_TRIM($text = NULL, $mask = NULL)
    {
        if (!is_string($text)) {
            return "[ERROR! paràmetres incorrectes STR_TRIM($text, $mask)]"; //TODO: internacionalitzar
        }
        if ($mask) {
            $ret = trim($text, $mask);
        } else {
            $ret = trim($text);
        }
        return $ret;
    }

    public static function STR_SUBTR($text = NULL, $start = 0, $len = NAN)
    {
        if (!(is_string($text) || !is_numeric($start))) {
            return "[ERROR! paràmetres incorrectes STR_SUBSTR($text, $start, $len)]"; //TODO: internacionalitzar
        }
        if (is_numeric($len)) {
            $ret = substr($text, $start, $len);
        } else {
            $ret = substr($text, $start);
        }
        return $ret;
    }

    public static function STR_REPLACE($search = NULL, $replace = NULL, $subject = NULL, $count = FALSE)
    {
        if (!(is_string($search) || is_array($search)) || !(is_string($replace) || is_array($replace)) || !is_string($subject)) {
            return "[ERROR! paràmetres incorrectes STR_REPLACE($search, $replace, $subject, $count)]"; //TODO: internacionalitzar
        }

        if (is_int($count)) {
            if ($count > 0) {
                $ret = implode($replace, static::_explode($search, $subject, $count + 1));
            } else {
                $aSubject = static::_explode($search, $subject);
                $len = count($aSubject);
                $limit = $len + $count;
                $ret = $aSubject[0];
                $csearch = is_array($search) ? $search[0] : $search;
                for ($i = 1; $i < $limit; $i++) {
                    $ret .= $csearch;
                    $ret .= $aSubject[$i];
                }
                for ($i = $limit; ($limit > 0 && $i < $len); $i++) {
                    $ret .= $replace;
                    $ret .= $aSubject[$i];
                }
            }
        } else {
            $ret = str_replace($search, $replace, $subject);
        }
        return $ret;
    }

    protected static function _explode($delim, $string)
    {
        if (is_array($delim)) {
            $nstring = str_replace($delim, $delim[0], $string);
            $ret = explode($delim[0], $nstring);
        } else {
            $ret = explode($delim, $string);
        }
        return $ret;
    }

    // $template pot tenir tres formes:
    // FIRST: retorna tota la fila com a json
    // FIRST[camp]: retorna el valor del camp com a string
    // {"a":{##camX##}, "b":LAST[xx], "c":10, "d":"hola", "f":true})#}: retorna la mateixa plantilla amb els valors reemplaçats com a json.
    protected static function formatItem($row, $ownKey, $template)
    {
        $jsonString = json_decode($template, true);

        if ($jsonString !== null) {
            $replaced = preg_replace_callback('/' . $ownKey . '\[(.*?)\]/', function ($matches) use ($row) {
                return $row[$matches[1]];
            }, $template);

            return $replaced;

        } else if ($template === $ownKey) {
            return json_encode($row);
        } else if (preg_match('/' . $ownKey . '\[(.*?)\]/', $template, $matches)) {
            return $row[$matches[1]];
        }
    }

    /**
     * Obté la suma dels camps $camp de: tota la $taula o només la fila indicada pel filtre
     * @param array $taula : taula a evaluar (array de arrays hash)
     * @param string $camp : camp de la taula que he de sumar
     * @param string $filter_field : camp de la taula que indica el filtre
     * @param type $filter_value : valor del filtre que cal comparar
     * @return numeric : suma total de los valores del campo $camp
     */
    public static function ARRAY_GET_SUM($taula, $camp, $filter_field=NULL, $filter_value=NULL, $strict=false)
    {
        $suma = 0;
        if (!empty($taula)) {
            if ($filter_field !== NULL && $filter_value !== NULL) {
                foreach ($taula as $fila) {
                    if (self::_arrayFilter($fila, $filter_value, $filter_field, $strict)) {
                        $suma += IocCommon::nz($fila[$camp], 0);
                    }
                }
            } else {
                foreach ($taula as $fila) {
                    $suma += IocCommon::nz($fila[$camp], 0);
                }
            }
        }
        return $suma;
    }

    /**
     * Ordena una taula per una coluna determinada
     * @param array $taula : taula que es vol ordenar
     * @param string $field : columna per la qual es vol ordenar la taula
     * @return array
     */
    public static function ARRAY_SORT($taula, $field) {
        $sorted_table = [];
        $aux = [];
        if (!empty($taula)) {
            foreach($taula as $key => $row) {
                $aux[$key] = $row[$field];
            }
            asort($aux);
            foreach ($aux as $key => $value) {
                $sorted_table[] = $taula[$key];
            }
        }
        return $sorted_table;
    }

    public static function GET_PERCENT($suma = 0, $valor = 0, $redondeo = 2)
    {
        return ($suma > 0 && $valor > 0) ? round($valor / $suma * 100, $redondeo) : 0;
    }

    public static function normalizeArg($arg)
    {
        if (is_array($arg)) {
            return $arg;
        } else if (strtolower(trim($arg)) == 'true') {
            return true;
        } else if (strtolower(trim($arg)) == 'false') {
            return false;
            // ALERTA[Xavi] Això no era correcte, intval retorna fals per estrings
//        } else if (is_int($arg)) {
//            return intval($arg);
        } else if (is_numeric($arg)) {
            if (strpos($arg, '.')) {
                return floatval($arg);
            } else {
                return intval($arg);
            }

        } else if (preg_match("/^\s*''(.*?)''\s*$/", $arg, $matches) === 1) {
            return static::normalizeArg($matches[1]);
        } else {
            return $arg;
        }

    }
}