<?php
class WiocclFunction extends WiocclInstruction
{

    protected $functionName = '';
    protected $arguments = [];


    protected function init($value)
    {
        if (preg_match('/(.*?)\((.*)\)/s', $value, $matches) === 0) {
            throw new Exception("Incorrect function structure");
        };

        $this->functionName = $matches[1];
        $this->arguments = $this->extractArgs($matches[2]);
    }


    protected function extractArgs($string)
    {
        $string = preg_replace("/''/", '"', $string);
//        $string = (new WiocclParser($string, $this->arrays, $this->dataSource))->getValue();
        $string = WiocclParser::getValue($string, $this->arrays, $this->dataSource, $this->resetables);
        $string = "[" . $string . "]";

        $jsonArgs = json_decode($string, true);
        //return $jsonArgs;

        //ALERTA: cal verificar quan es produeix una situació en la que $jsonArgs té un valor incorrecte
        return ($jsonArgs!==NULL || !is_array($jsonArgs)) ? $jsonArgs : [];
    }
    
    protected function resolveOnClose($result) {
        $this->init($result);
        $result = call_user_func_array(array($this, $this->functionName), $this->arguments);
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
                $ret = $this->_countValueInFieldsObjectArray($array, $fields);
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
        $compliant = false;
        $cont=0;
        foreach ($array as $item) {
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
        $compliant = false;
        $cont=0;
        foreach ($array as $item) {
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
        $compliantField = true;
        $compliantValue = false;
        $cont=0;
        foreach ($array as $item) {
            for($indFields=0; $compliantField && $indFields<count($fields); $indFields++) {
                // ALERTA[Xavi] $values no està definit, ha de ser $valuesOfValues?
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
        $compliant = true;
        $cont=0;
        foreach ($array as $item) {
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

    protected function DATE($date, $sep="-")
    {
        return date("d".$sep."m".$sep."Y", strtotime(str_replace('/', '-', $date)));
    }

    protected function LONG_DATE($date, $includeDay)
    {
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
        if(!is_numeric($days) || !is_numeric($months) || !is_numeric($years)){
            return "[ERROR! paràmetres incorrectes ($days, $months, $years)]"; //TODO: internacionalitzar
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
    protected function ARRAY_GET_VALUE($key, $array)
    {
        return  $array[$key];
    }
    
    // ALERTA: El paràmetre de la funció no ha d'anar entre cometes, ja es tracta d'un JSON vàlid
    protected function ARRAY_LENGTH($array)
    {
        return count($array);
    }

    protected function COUNTDISTINCT($array, $fields)
    {
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
        return $this->formatItem($array[0], 'FIRST', $template);
    }

    protected function LAST($array, $template)
    {
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
            return "[ERROR! paràmetres incorrectes ($value1, $value2)]"; //TODO: internacionalitzar
        }
        return $value1 - $value2;
    }

    protected function SUMA($value1, $value2)
    {
        if(!is_numeric($value1) || !is_numeric($value2)){
            return "[ERROR! paràmetres incorrectes ($value1, $value2)]"; //TODO: internacionalitzar
        }
        return $value1 + $value2;
    }

    protected function UPPERCASE($value1, $value2, $value3=0)
    {
        $ret;
        if(!is_numeric($value2) || !is_numeric($value3)){
            return "[ERROR! paràmetres incorrectes ($value1, $value2, $value3)]"; //TODO: internacionalitzar
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
    protected function UCFIRST($value) {
        return ucfirst($value);
    }
    
    protected function STR_CONTAINS($subs, $string){
        return (strpos($string, $subs)!==FALSE)?"true":"false";
    }

    // Uppercase només pel primer caràcter
    protected function STR_REPLACE($search, $replace, $subject, $count=FALSE) {
        if(is_int($count)){
            if($count>0){
                $ret = implode($replace, $this->explode($search, $subject, $count+1));
            }else{
                $aSubject = $this->explode($search, $subject);
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
    
    protected function explode($delim, $string){
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


}