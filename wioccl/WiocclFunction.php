<?php
class WiocclFunction extends WiocclInstruction
{

    protected $functionName = '';
    protected $arguments = [];
    protected $rawArguments = '';

    public function __construct($value = null, array $arrays = array(), array $dataSource = array(), $resetables = NULL, $parentInstruction = NULL) {

        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        $this->pauseStructureGeneration();
    }

    protected function init($value, $tokenEnd)
    {
        if (preg_match('/(.*?)\((.*)\)/s', $value, $matches) === 0) {
            throw new Exception("Incorrect function structure");
        };

        $this->functionName = $matches[1];

//        $this->pauseStructureGeneration();

        $this->arguments = $this->extractArgs($matches[2]);

//        ALERTA! Aquests no son els rawArguments, son els arguments ja parsejats!!

        $this->generateRawValue($rawValue, $this->currentToken['tokenIndex']+1, $tokenEnd['tokenIndex']-1);

        // Els arguments son els valors que es troben entre el primer ( i l'últim )

        $paramStart = strpos($rawValue, '(') +1;
        $paramEnd = strrpos($rawValue, ')');

        $this->rawArguments = substr($rawValue, $paramStart, $paramEnd - $paramStart);

//        $this->resumeStructureGeneration();

        if($this->arguments==null){
            $this->arguments=[];
        }
    }


    protected function extractArgs($string)
    {
        $string = preg_replace('/(^|\\s)\'\'\'/', '$1"\'', $string);
        $string = preg_replace('/\'\'\'(\\s|$)/', '\'"$1', $string);
        $string = preg_replace("/''/", '"', $string);
//        $string = (new WiocclParser($string, $this->arrays, $this->dataSource))->getValue();
        $string = WiocclParser::getValue($string, $this->arrays, $this->dataSource, $this->resetables);
        $string = "[" . $string . "]";

        $jsonArgs = json_decode($string, true);
        //return $jsonArgs;

        //ALERTA: cal verificar quan es produeix una situació en la que $jsonArgs té un valor incorrecte
        return ($jsonArgs==NULL || !is_array($jsonArgs)) ? [] : $jsonArgs;
    }

    protected function resolveOnClose($result, $tokenEnd) {
        $this->init($result, $tokenEnd);
        $method = array($this, $this->functionName);
        if(is_callable($method)){
            $result = call_user_func_array($method, $this->arguments);
        }else{
            $result = "[ERROR! No existeix la funció ${$method[1]}]";
        }

        $this->resumeStructureGeneration();

        $this->close($result, $tokenEnd);


        // només s'ha de canviar el primer element del format
        if ($this->rawArguments) {
            $this->item->open = sprintf($this->currentToken['extra']['opening-format'], $this->functionName, "%s");
        } else {
            $this->item->open = sprintf($this->currentToken['extra']['opening-format'], $this->functionName, "");
        }

        $this->item->attrs = $this->rawArguments;
        $this->item->close = "";
        return $result;
    }




    protected function COUNTINARRAY($array, $fields, $values=NULL){
        if($values==NULL){
            if(is_array($fields)){
                $ret = $this->_countValuesInArray($array, $fields);
            }else{
                $ret = $this->_countValueInArray($array, $fields);
            }
        }else if(is_array($fields)){
            if(count($values)>0 && is_array($values[0])){
                $ret = $this->_countValuesInFieldsOfArray($array, $fields, $values);
            }else{
                $ret = $this->_countValueInFieldsObjectArray($array, $fields, $values);
            }
        }else{
            if(is_array($values)){
                $ret = $this->_countValuesInFieldOfArray($array, $fields, $values);
            }else{
                $ret = $this->_countValueInFieldOfArray($array, $fields, $values);
            }
        }
        return $ret;
    }

    private function _countValueInArray($array, $value){
        $cont=0;
        foreach ($array as $item) {
            if ($item==$value) {
                $cont++;
            }
        }
        return $cont;
    }

    private function _countValuesInArray($array, $values){
        $cont=0;
        foreach ($array as $item) {
            $compliant = false;
            for($ind=0; !$compliant && $ind<count($values); $ind++) {
                $compliant = $item==$values[$ind];
            }
            if ($compliant) {
                $cont++;
            }
        }
        return $cont;
    }

    private function _countValueInFieldOfArray($array, $field, $value){
        $cont=0;
        foreach ($array as $item) {
            if ($item[$field]==$value) {
                $cont++;
            }
        }
        return $cont;
    }

    private function _countValuesInFieldOfArray($array, $field, $values){
        $cont=0;
        foreach ($array as $item) {
            $compliant = false;
            for($ind=0; !$compliant && $ind<count($values); $ind++) {
                $compliant = $item[$field]==$values[$ind];
            }
            if ($compliant) {
                $cont++;
            }
        }
        return $cont;
    }

    private function _countValuesInFieldsOfArray($array, $fields, $valuesOfValues){
        $cont=0;
        foreach ($array as $item) {
            $compliantField = true;
            for($indFields=0; $compliantField && $indFields<count($fields); $indFields++) {
                $compliantValue = false;
                for($indValues=0; !$compliantValue && $indValues<count($values[$indFields]); $indValues++) {
                    $compliantValue = $item[$fields[$indFields]]==$valuesOfValues[$indFields][$indValues];
                }
                $compliantField = $compliantValue;
            }
            if ($compliantField) {
                $cont++;
            }
        }
        return $cont;
    }

    private function _countValueInFieldsObjectArray($array, $fields, $values){
        $cont=0;
        foreach ($array as $item) {
            $compliant = true;
            for($ind=0; $compliant && $ind<count($fields); $ind++) {
                $compliant = $item[$fields[$ind]]==$values[$ind];
            }
            if ($compliant) {
                $cont++;
            }
        }
        return $cont;
    }

    protected function IS_STR_EMPTY($text=""){
        return empty($text)?"true":"false";
    }

    protected function YEAR($date=NULL){
        if($date==NULL){
            $ret = date("Y");
        }else{
            date("Y", strtotime(str_replace('/', '-', $date)));
        }
        return $ret;
    }

    protected function DATE($date=NULL, $sep="-")
    {
        if(!is_string($date)){
            return "[ERROR! paràmetres incorrectes DATE($date, $sep)]"; //TODO: internacionalitzar
        }
        return date("d".$sep."m".$sep."Y", strtotime(str_replace('/', '-', $date)));
    }

    protected function LONG_DATE($date=NULL, $includeDay=FALSE)
    {
        if(!is_string($date)){
            return "[ERROR! paràmetres incorrectes LONG_DATE($date, $includeDay)]"; //TODO: internacionalitzar
        }
        $format = '';

        if ($includeDay) {
            //$format .= "l, ";
            $format .= "%A, ";
        }

        setlocale(LC_TIME,"ca_ES.utf8");
        $format .= "%e de %B de %G";

        return strftime($format, strtotime($date));
        //return date($format, strtotime(str_replace('/', '-', $date)));
    }

    protected function SUM_DATE($date, $days, $months=0, $years=0, $sep="-") {
        if(!is_string($date) || !is_numeric($days) || !is_numeric($months) || !is_numeric($years)){
            return "[ERROR! paràmetres incorrectes SUM_DATE($days, $months, $years)]"; //TODO: internacionalitzar
        }

        $newDate = $date;

        if ($days>0) {
            $calculated = strtotime("+" . $days . " day", strtotime($date));
            $newDate = date("Y".$sep."m".$sep."d", $calculated);
        }

        if ($months>0) {

            $calculated = strtotime("+" . $months . " month", strtotime($newDate));
            $newDate = date("Y".$sep."m".$sep."d", $calculated);
        }

        if ($years>0) {
            $calculated = strtotime("+" . $years . " year", strtotime($newDate));
            $newDate = date("Y".$sep."m".$sep."d", $calculated);
        }

        return $newDate;
    }

    protected function IN_ARRAY($value, $array){
        $ret = in_array($value, $array);
        return $ret;
    }

    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    protected function SEARCH_VALUE($toSearch, $array, $column=NULL)
    {
        if($column!=NULL){
            $key = array_search($toSearch, array_column($array, $column));
        }else{
            $key = array_search($toSearch, $array);
        }
        $ret = $key ===false?"null":$array[$key];
        return self::_normalizeValue($ret);
    }

    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    protected function SEARCH_KEY($toSearch, $array, $column=NULL)
    {
        if($column!=NULL){
            $key = array_search($toSearch, array_column($array, $column));
        }else{
            $key = array_search($toSearch, $array);
        }
        return $key;
    }
    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    protected function ARRAY_GET_VALUE($key, $array, $defaultValue=FALSE)
    {
        if($key===null || !is_array($array)){
            if($defaultValue===false){
                return "[ERROR! paràmetres incorrectes ARRAY_GET_VALUE($key, $array)]"; //TODO: internacionalitzar
            } else {
                return $defaultValue;
            }
        }elseif($key < 0 || $key >= count($array)){
            if($defaultValue===false){
                return "[ERROR! key fora de rang ARRAY_GET_VALUE($key, $array)]"; //TODO: internacionalitzar
            } else {
                return $defaultValue;
            }
        }
        return  isset($array[$key])?$array[$key]:$defaultValue;
    }

    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    protected function ARRAY_LENGTH($array=NULL) {
        if ($array===NULL) {
            return 0;
        }elseif (!is_array($array)){
            return "[ERROR! paràmetres incorrectes ARRAY_LENGTH($array)]"; //TODO: internacionalitzar
        }
        return count($array);
    }

    protected function COUNTDISTINCT($array, $fields)
    {
        if(!is_array($array) || !is_array($fields)){
            return "[ERROR! paràmetres incorrectes COUNTDISTINCT($array, $fields)]"; //TODO: internacionalitzar
        }

        $unique = [];


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


    protected function FIRST($array, $template)
    {
        if(!is_array($array) || !is_string($template)){
            return "[ERROR! paràmetres incorrectes FIRST($array, $template)]"; //TODO: internacionalitzar
        }
        return $this->formatItem($array[0], 'FIRST', $template);
    }

    protected function LAST($array, $template)
    {
        if(!is_array($array) || !is_string($template)){
            return "[ERROR! paràmetres incorrectes LAST($array, $template)]"; //TODO: internacionalitzar
        }
        return $this->formatItem($array[count($array)-1], 'LAST', $template);
    }

    private static function _normalizeValue($ret){
        if(is_array($ret) || is_object($ret)){
            $ret= json_encode($ret);
//        }else if(is_string($ret)){
//            $ret = "\"$ret\"";
        }
        return $ret;
    }

    private static function _compareMultiObjectFields($obj1, $obj2, $type, $fields, $pos=0){
        if($pos >= count($fields)){
            $ret = substr($type, 1, 1)==="=";
        }else if($obj1[$fields[$pos]]==$obj2[$fields[$pos]]){
            $ret = self::_compareMultiObjectFields($obj1, $obj2, $fields, $type, $pos+1);
        }else{
            $ret = self::_compareSingleValues($obj1[$fields[$pos]], $obj2[$fields[$pos]], $type);
        }
        return $ret;
    }

    private static function _compareSingleObjectFields($obj1, $obj2, $type, $field){
        return self::_compareSingleValues($obj1[$field], $obj2[$field], $type);
    }

    private static function _compareSingleValues($v1, $v2, $type){
        $ret = FALSE;
        switch ($type){
            case "<":
                $ret = $v1<$v2;
                break;
            case ">":
                $ret = $v1>$v2;
                break;
            case "<=":
                $ret = $v1<=$v2;
                break;
            case ">=":
                $ret = $v1>=$v2;
                break;
        }
        return $ret;
    }

    protected function MIN($array, $template="MIN", $fields=NULL){
        $valueFromTemplate = FALSE;
        if($fields==NULL){ //ARRAY
            $compare = "_compareSingleValues";
            $valueFromTemplate = $template!=="MIN";
        } else if(is_array($fields)){ //OBJECT and multi field comparation
            $compare = "_compareMultiObjectFields";
        }else{  //OBJECT and single fiels comparation
            $compare = "_compareSingleObjectFields";
        }
        if(count($array)>0){
            $min=0;
            for($pos=1; $pos<count($array); $pos++){
                if($valueFromTemplate){
                    $v1 = $this->formatItem($array[$pos], 'MIN', $template);
                    $v2 = $this->formatItem($array[$min], 'MIN', $template);
                }else{
                    $v1 = $array[$pos];
                    $v2 = $array[$min];
                }
                if(self::{$compare}($v1, $v2, "<", $fields)){
                    $min = $pos;
                }
            }
        }else{
            return "[ERROR! array buit]"; //TODO: internacionalitzar
        }
        return $this->formatItem($array[$min], 'MIN', $template);
    }

    protected function MAX($array, $template="MAX", $fields=NULL){
        $valueFromTemplate = FALSE;
        if($fields==NULL){ //ARRAY
            $compare = "_compareSingleValues";
            $valueFromTemplate = $template!=="MAX";
        } else if(is_array($fields)){ //OBJECT and multi field comparation
            $compare = "_compareMultiObjectFields";
        }else{  //OBJECT and single fiels comparation
            $compare = "_compareSingleObjectFields";
        }
        if(count($array)>0){
            $max=0;
            for($pos=1; $pos<count($array); $pos++){
                if($valueFromTemplate){
                    $v1 = $this->formatItem($array[$pos], 'MAX', $template);
                    $v2 = $this->formatItem($array[$max], 'MAX', $template);
                }else{
                    $v1 = $array[$pos];
                    $v2 = $array[$max];
                }
                if(self::{$compare}($array[$pos], $array[$max], ">", $fields)){
                    $max = $pos;
                }
            }
        }else{
            return "[ERROR! array buit]"; //TODO: internacionalitzar
        }
        return $this->formatItem($array[$max], 'MAX', $template);
    }

    protected function SUBS($value1, $value2)
    {
        if(!is_numeric($value1) || !is_numeric($value2)){
            return "[ERROR! paràmetres incorrectes SUBS($value1, $value2)]"; //TODO: internacionalitzar
        }
        return $value1 - $value2;
    }

    protected function SUMA($value1="NULL", $value2="NULL")
    {
        if(!is_numeric($value1) || !is_numeric($value2)){
            return "[ERROR! paràmetres incorrectes SUMA($value1, $value2)]"; //TODO: internacionalitzar
        }
        return $value1 + $value2;
    }

    protected function UPPERCASE($value1, $value2, $value3=0)
    {
        $ret;
        if(!is_numeric($value2) || !is_numeric($value3)){
            return "[ERROR! paràmetres incorrectes UPPERCASE($value1, $value2, $value3)]"; //TODO: internacionalitzar
        }
        if($value3==0){
            $value3 = $value2;
            $value2 = 0;
        }
        $ret = strtoupper(substr($value1, $value2, $value3));
        if($value3< strlen($value1)){
            $ret .= substr($value1, $value3, strlen($value1));
        }
        return $ret;
    }

    // Uppercase només pel primer caràcter
    protected function UCFIRST($value=NULL) {
        if(!is_string($value)){
            return "[ERROR! paràmetres incorrectes UCFIRST($value)]"; //TODO: internacionalitzar
        }
        return ucfirst($value);
    }

    protected function STR_CONTAINS($subs=NULL, $string=NULL){
        if(!is_string($subs) || !is_string($string)){
            return "[ERROR! paràmetres incorrectes STR_CONTAINS($subs, $string)]"; //TODO: internacionalitzar
        }
        return (strpos($string, $subs)!==FALSE)?"true":"false";
    }

    protected function EXPLODE($delimiter=NULL, $string=NULL, $limit=false){
        if(!is_string($delimiter)||!is_string($string)){
            return "[ERROR! paràmetres incorrectes EXPLODE($delimiter, $string, $limit)]"; //TODO: internacionalitzar
        }

        if(!$limit){
            $limit = PHP_INT_MAX;
        }
        $ret = explode($delimiter, $string, $limit);
        return self::_normalizeValue($ret);
    }

    protected function STR_TRIM($text=NULL, $mask=NULL) {
        if(!is_string($text)){
            return "[ERROR! paràmetres incorrectes STR_TRIM($text, $mask)]"; //TODO: internacionalitzar
        }
        if($mask){
            $ret = trim($text, $mask);
        }else{
            $ret = trim($text);
        }
        return $ret;
    }

    protected function STR_SUBTR($text=NULL, $start=0, $len=NAN) {
        if(!(is_string($text)|| !is_numeric($start))){
            return "[ERROR! paràmetres incorrectes STR_SUBSTR($text, $start, $len)]"; //TODO: internacionalitzar
        }
        if(is_numeric ($len)){
            $ret = substr($text, $start, $len);
        }else{
            $ret = substr($text, $start);
        }
        return $ret;
    }

    protected function STR_REPLACE($search=NULL, $replace=NULL, $subject=NULL, $count=FALSE) {
        if(!(is_string($search)||is_array($search)) || !(is_string($replace)|| is_array($replace)) || !is_string($subject)){
            return "[ERROR! paràmetres incorrectes STR_REPLACE($search, $replace, $subject, $count)]"; //TODO: internacionalitzar
        }

        if(is_int($count)){
            if($count>0){
                $ret = implode($replace, $this->_explode($search, $subject, $count+1));
            }else{
                $aSubject = $this->_explode($search, $subject);
                $len = count($aSubject);
                $limit = $len+$count;
                $ret=$aSubject[0];
                $csearch = is_array($search)?$search[0]:$search;
                for($i= 1; $i<$limit; $i++){
                    $ret .= $csearch;
                    $ret .= $aSubject[$i];
                }
                for($i= $limit; ($limit>0 && $i<$len); $i++){
                    $ret .= $replace;
                    $ret .= $aSubject[$i];
                }
            }
        }else{
            $ret = str_replace($search, $replace, $subject);
        }
        return $ret;
    }

    protected function _explode($delim, $string){
        if(is_array($delim)){
            $nstring = str_replace($delim, $delim[0], $string);
            $ret = explode($delim[0], $nstring);
        }else{
            $ret = explode($delim, $string);
        }
        return $ret;
    }

        // $template pot tenir tres formes:
    // FIRST: retorna tota la fila com a json
    // FIRST[camp]: retorna el valor del camp com a string
    // {"a":{##camX##}, "b":LAST[xx], "c":10, "d":"hola", "f":true})#}: retorna la mateixa plantilla amb els valors reemplaçats com a json.
    protected function formatItem($row, $ownKey, $template)
    {
        $jsonString = json_decode($template, true);

        if ($jsonString !== null) {
            $replaced = preg_replace_callback('/'.$ownKey.'\[(.*?)\]/', function ($matches) use ($row) {
                return $row[$matches[1]];
            }, $template);

            return $replaced;

        } else if ($template === $ownKey) {
            return json_encode($row);
        } else if (preg_match('/'.$ownKey.'\[(.*?)\]/', $template, $matches)) {
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
    protected function ARRAY_GET_SUM ($taula, $camp, $filter_field=NULL, $filter_value=NULL) {
        $suma = 0;
        if (!empty($taula)) {
            if ($filter_field !== NULL && $filter_value !== NULL) {
                foreach ($taula as $fila) {
                    if ($fila[$filter_field] == $filter_value) {
                        $suma += $fila[$camp];
                    }
                }
            }else {
                foreach ($taula as $fila) {
                    $suma += $fila[$camp];
                }
            }
        }
        return $suma;
    }

    protected function GET_PERCENT ($suma=0, $valor=0, $redondeo=2) {
        return ($suma > 0 && $valor > 0) ? round($valor / $suma * 100, $redondeo) : 0;
    }
}

