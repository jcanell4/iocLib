<?php
/**
 * ProjectUpdateProcessor: clases para la actualización de los datos de proyecto
 *                         a partir de los parámetros de configuración del tipo de proyecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ManagerProjectUpdateProcessor{
    
    public static function updateAll($arraytaula, &$projectMetaData){
        $toUpdate=array();
        
        foreach ($projectMetaData as $key => $value){
            $toUpdate[$key] = $value;
        }
        $processArray = array();
        try{

            foreach ($arraytaula as $elem) {
                if($elem["type"] !== "noprocess"){
                    $processor = ucwords($elem['type'])."ProjectUpdateProcessor";
                    if ( !isset($processArray[$processor]) ) {
                        $processArray[$processor] = new $processor;
                    }
                    $processArray[$processor]->init($elem['value'], $elem['parameters']);
                    $processArray[$processor]->runProcess($toUpdate);
                }
            }
        } catch (Exception $e){
            $toUpdate = array();
        }
        
        foreach ($toUpdate as $k => $v){
            $projectMetaData[$k] = $v;
        }
        return count($toUpdate)>0;
    }    
}

abstract class AbstractProjectUpdateProcessor{
    protected abstract function getFieldValue($fieldValue);
    protected $value;
    protected $params;
    protected $idField;
    
    public function init($value, $params) {
        $this->value = $value;
        $this->setParams($params);
    }
    
    public function getIdField(){
        return $this->idField;
    }
    
    public function getValue(){
        return $this->value;
    }
    
    public function hasParam($key){
        return isset($this->params[$key]);
    }
    
    public function getParam($key){
        return $this->params[$key];
    }
    
    public function getParams(){
        return $this->params;
    }
    
    private function setParams($p){
        if(is_string($p)){
            $this->params = json_decode($p, TRUE);
        }else{
            $this->params = $p;
        }        
    }

    public function runProcess(&$projectMetaData){
        if(isset($this->params["field"])){
            $this->runProcessField($this->params["field"], $projectMetaData);
        }        
        if(isset($this->params["fields"])){
            $this->runProcessFields($this->params["fields"], $projectMetaData);
        }
    }

    protected function runProcessFields($fields, &$projectMetaData){
        for($this->idField=0;$this->idField<count($fields);$this->idField++) {
            $this->runProcessField($fields[$this->idField], $projectMetaData);
        }
    }    

    protected function runProcessField($field, &$projectMetaData){
        FieldProjectUpdateProcessor::runProcessField($this, $field, $projectMetaData);
    }

    public function returnType($value, $type){
        if($type==="date" || $type="future_date"){
            if(is_string($value)){
                $fecha = DateTime::createFromFormat('d#m#Y', $value);
            }else{
                $fecha = $value;
            }
            if($type=="future_date"){
                $today = new DateTime();
                if($fecha <  $today){                    
                    $fecha->add(new DateInterval('P1Y'));
                }
            }
            $ret =  $fecha->format('Y-m-d');
        }else{
            $ret = settype($value, $type);
        }
        return $ret;
    }
    
    public function concat($str, $objectValues){
        foreach ($objectValues as $objectValue){
            switch ($objectValue["type"]){
                case "literal":
                    $str .= $objectValue["value"];
                    break;
                case "function":
                    if(is_callable([$this, $objectValue["name"]])){
                        $str .= call_user_func([$this, $objectValue["name"]], $objectValue["parameters"]);
                    }else if(is_callable($objectValue["name"])){
                        $str .= call_user_func($objectValue["name"], $objectValue["parameters"]);
                    }else{
                        //ERROR
                    }
                    break;
            }
        }
        return $str;
    }
}

// Updated by marjose
// to manage arrays of objects
// $obj contains de data coming from de admconfig project related
// $field contains the field name to change
// $projectMetaData contains all the data of the project being updated

class FieldProjectUpdateProcessor{
    public static function runProcessField($obj, $field, &$projectMetaData){
        if (isset($projectMetaData[$field])) {
            $projectMetaData[$field] = self::_resolveUpdateValue($obj, $projectMetaData[$field]);
//            $projectMetaData[$field] = $obj->getFieldValue($projectMetaData[$field]);
//            if($obj->hasParam("concat")){
//                $projectMetaData[$field] = $obj->concat($projectMetaData[$field], $obj->getParam("concat"));
//            }
//            if($obj->hasParam("returnType")){
//                $projectMetaData[$field] = $obj->returnType($projectMetaData[$field], $obj->getParam("returnType"));
//            }            
        }else if(strpos($field, "#")>0){
            $esElementArray = false;

            $b = false;
            $dataPosIni = $data = &$projectMetaData;
            $akeys = explode("#", $field);
            $lim = count($akeys)-1;
            for($i=0; !$b && $i<$lim; $i++){
                //akeys contains field to be accessed
                //if the field contains a number, we are accessing an element of an objectarray, 
                //it must be coverted from string to int
                if (is_numeric($akeys[$i])) {
                   $akeys[$i] = intval($akeys[$i]); 
                   $b = !isset($data[$akeys[$i]]);
                   if(!$b){
                       // saves the data field position
                        $dataPosIni = $data;
                        $esElementArray = true;
                       // convert $data from JSON string to PHP array to be able to access the position specified by $akeys
                        $phpArray = json_decode($data, true);
                        $data = &$phpArray[$akeys[$i]];
                   }
                }else{ //When element is not a number, thus it is an object and not an element of an objectarray                
                    $b = !isset($data[$akeys[$i]]);
                    if(!$b){
                        $data = &$data[$akeys[$i]];
                    }
                }
            }
            if(!$b){
                $data[$akeys[$lim]]= self::_resolveUpdateValue($obj, $data[$akeys[$lim]]);
                
                if($esElementArray){
                    $stringTempo =  json_encode($phpArray);
                    copy($stringTempo, $dataPosIni);
                    $dataPosIni = json_encode($phpArray);
                }
            }
        }        
    }    
    
    private static function _resolveUpdateValue($obj, $currentfieldValue){
        $ret = $obj->getFieldValue($currentfieldValue);
        if($obj->hasParam("concat")){
            $ret = $obj->concat($ret, $obj->getParam("concat"));
        }
        if($obj->hasParam("returnType")){
            $ret = $obj->returnType($ret, $obj->getParam("returnType"));
        }            
        return $ret;
    }
}

class ArrayFieldProjectUpdateProcessor{

    public static function runProcessField($obj, $field, &$projectMetaData){
        if (isset($projectMetaData[$field])) {
            $keysOfArray = $obj->getParam("keysOfArray");
            $conditions = $obj->getParam("conditions");
            $idField = $obj->getIdField();
            if (is_array($keysOfArray) && array_diff_key($keysOfArray,array_keys(array_keys($keysOfArray)))){
                foreach ($keysOfArray[$field] as $arrayKey){
                    self::_runProcessField($obj, $field, $projectMetaData, $arrayKey, $conditions[$field]);
                }            
            }else {
                foreach ($keysOfArray[$idField] as $arrayKey){
                    self::_runProcessField($obj, $field, $projectMetaData, $arrayKey, $conditions[$idField]);
                }
            }
        }
    }
    
    private static function _runProcessField($obj, $field, &$projectMetaData, $arrayKey, $conditions=NULL){
        if (is_string($projectMetaData[$field])){
            $projectMetaData[$field] = json_decode($projectMetaData[$field], TRUE);
        }
        if ($projectMetaData[$field] && is_array($projectMetaData[$field])) {
            for ($i=0; $i<count($projectMetaData[$field]); $i++) {
                $condition = TRUE;
                if (is_array($conditions) && !empty($conditions)) {
                    $condition = self::_evalCondition($projectMetaData[$field][$i], $conditions);
                }
                if ($condition) {
                    $projectMetaData[$field][$i][$arrayKey] = $obj->getFieldValue($projectMetaData[$field][$i][$arrayKey]);
                    if ($obj->hasParam("concat")){
                        $projectMetaData[$field][$i][$arrayKey] = $obj->concat($projectMetaData[$field][$i][$arrayKey], $obj->getParam("concat"));
                    }
                    if ($obj->hasParam("returnType")){
                        $projectMetaData[$field][$i][$arrayKey] = $obj->returnType($projectMetaData[$field][$i][$arrayKey], $obj->getParam("returnType"));
                    }
                }
            }
        }
    }

    private static function _evalCondition($field, $conditions) {
        $condition = TRUE;
        $orcondition = FALSE;
        foreach ($conditions as $key => $value) {
            if (is_numeric($key) && is_array($value)) {
                $andcondition = TRUE;
                foreach ($value as $k => $v) {
                    //$andcondition &= ($field[$k] === $v);
                    $andcondition &= self::__equalCompare__($field[$k], $v);
                }
                $orcondition |= $andcondition;
            }else {
                //$condition &= ($field[$key] === $value);
                $condition &= self::__equalCompare__($field[$key], $value);
            }
        }
        return (isset($andcondition)) ? $orcondition : $condition;
    }
    
    public static function test_equalcompare($v1, $v2){
        return self::__equalCompare__($v1, $v2);
    }
    
    /**
     * Compara si dos valors són iguals o no. Els valors han de ser de tipus string però 
     * si algun d'ells es troba tancat entre els caracters [] o els caracters (), es consierarà 
     * que conté multivalors separats per comes. Si el caracter de tancament és [], la comparació 
     * serà certa si hi ha conincidència amb algun del múltiples valors. Per contra si el 
     * els caràcters de tancament són (), la comparació només serà certa si l'altre valor és 
     * també multivalor i coincideixen tots els seus elements.
     * Exemples:
     * - $v1 = "Cadena única"
     * - $v2 = "Cadena única"
     *  Resultat = true
     * - $v1 = "Cadena única"
     * - $v2 = "Una altre cadena"
     *  Resultat = false
     * - $v1 = "Cadena única"
     * - $v2 = "[Cadena única, Una altre cadena]"
     *  Resultat = true perquè hi ha un valor a $v2 coincident
     * - $v1 = "Cadena única"
     * - $v2 = "(Cadena única, Una altre cadena)"
     *  Resultat = false perquè hi ha un valor a $v2 que no coeincidex amb $v1
     * - $v1 = "Cadena única"
     * - $v2 = "(Cadena única)"
     *  Resultat = true perquè tots els valors de $v2 coincidexen amb la cadena de $v1
     * - $v1 = "Cadena única"
     * - $v2 = "(Cadena única, Cadena única)"
     *  Resultat = true perquè tots els valors de $v2 coincidexen amb la cadena de $v1
     * - $v1 = "[cadena 1, cadena 2]"
     * - $v2 = "[cadena 3, cadena 4]"
     *  Resultat = false perquè cap dels valors de $v1 conicideix amb cap dels valors de $v2
     * - $v1 = "[cadena 1, cadena 2]"
     * - $v2 = "[cadena 3, cadena 4, cadena 1]"
     *  Resultat = True perquè almenys un dels valors de $v1 conicideix amb un dels valors de $v2
     * - $v1 = "(cadena 1, cadena 2)"
     * - $v2 = "[cadena 3, cadena 2, cadena 1]"
     *  Resultat = True perquè tots els valors de $v1 conicideixen amb algun dels valors de $v2
     * - $v1 = "(cadena 1, cadena 2)"
     * - $v2 = "[cadena 3, cadena 4, cadena 1]"
     *  Resultat = False perquè no tots els valors de $v1 conicideixen amb algun dels valors de $v2
     * - $v1 = "(cadena 1, cadena 2)"
     * - $v2 = "(cadena 3, cadena 2, cadena 1)"
     *  Resultat = False perquè hi ha un element de $v2 al que no li correcpon cap element de $v1
     * - $v1 = "(cadena 1, cadena 2)"
     * - $v2 = "(cadena 2, cadena 2, cadena 1)"
     *  Resultat = True perquè tots els valors de $v1 i $v2 tenen una correspondència
     * 
     * És una funció commutativa. Ésa a dir és indiferent fer ::__equalCompare__($v1, $v2) 
     * que ::__equalCompare__($v2, $v1)
     * @param type $v1
     * @param type $v2
     */
    private static function __equalCompare__($v1, $v2){
        $v1Type=0;
        $v2Type=0;
        $v1Value = $v1?trim($v1):"";
        $v2Value = $v2?trim($v2):"";
        if($v1Value[0]=="[" && $v1Value[-1]=="]"){
            $v1Type = 1;
            $v1Value = preg_split("/ *\, */", substr($v1Value, 1, -1));
        }elseif($v1Value[1]=="[" && $v1Value[-1]=="]"){
            $v1Type = 1;
            $v1Value = preg_split("/ *\, */", substr($v1Value, 2, -1));
        }elseif($v1Value[1]=="[" && $v1Value[-2]=="]"){
            $v1Type = 1;
            $v1Value = preg_split("/ *\, */", substr($v1Value, 2, -2));
        }elseif($v1Value[0]=="(" && $v1Value[-1]==")"){
            $v1Type = 2;    
            $v1Value = preg_split("/ *\, */", substr($v1Value, 1, -1));
        }elseif($v1Value[1]=="(" && $v1Value[-1]==")"){
            $v1Type = 2;    
            $v1Value = preg_split("/ *\, */", substr($v1Value, 2, -1));
        }elseif($v1Value[1]=="(" && $v1Value[-2]==")"){
            $v1Type = 2;    
            $v1Value = preg_split("/ *\, */", substr($v1Value, 2, -2));
        }
        if($v2Value[0]=="[" && $v2Value[-1]=="]"){
            $v2Type = 1;
            $v2Value = preg_split("/ *\, */", substr($v2Value, 1, -1));
        }elseif($v2Value[1]=="[" && $v2Value[-1]=="]"){
            $v2Type = 1;
            $v2Value = preg_split("/ *\, */", substr($v2Value, 2, -1));
        }elseif($v2Value[1]=="[" && $v2Value[-2]=="]"){
            $v2Type = 1;
            $v2Value = preg_split("/ *\, */", substr($v2Value, 2, -2));
        }elseif($v2Value[0]=="(" && $v2Value[-1]==")"){
            $v2Type = 2;    
            $v2Value = preg_split("/ *\, */", substr($v2Value, 1, -1));
        }elseif($v2Value[1]=="(" && $v2Value[-1]==")"){
            $v2Type = 2;    
            $v2Value = preg_split("/ *\, */", substr($v2Value, 2, -1));
        }elseif($v2Value[1]=="(" && $v2Value[-2]==")"){
            $v2Type = 2;    
            $v2Value = preg_split("/ *\, */", substr($v2Value, 2, -2));
        }
        if($v1Type==0){
            if($v2Type==0){
                $ret = self::__equalCompareStringToString__($v1Value, $v2Value);
            }else{
                $ret = self::__equalCompareStringToArray__($v1Value, $v2Value, $v2Type);
            }
        }else{
            if($v2Type==0){
                $ret = self::__equalCompareStringToArray__($v2Value, $v1Value, $v1Type);
            }else{
                $ret = self::__equalCompareArrayToArray__($v1Value, $v2Value, $v1Type, $v2Type);
            }            
        }
        return $ret;
    }
    
    private static function __equalCompareStringToString__($v1, $v2){
        return $v1===$v2;
    }

    private static function __equalCompareStringToArray__($v1, $v2, $operator){
        $ret=true;
        if($operator==1){
            $ret = in_array($v1, $v2);
        }else{
            foreach ($v2 as $elem){
                $ret = $ret && $v1==$elem;
            }
        }
        return $ret;
    }

    private static function __equalCompareArrayToArray__($v1, $v2, $operator1, $operator2){
        if($operator1==1 && $operator2==1){
            $ret =false;
            foreach ($v1 as $elem){
                $ret = $ret || in_array($elem, $v2);
            }
        }else{
            $ret =true;
            if($operator1==2){
                foreach ($v1 as $elem){
                    $ret = $ret && in_array($elem, $v2);
                }
            }
            if($operator2==2){
                foreach ($v2 as $elem){
                    $ret = $ret && in_array($elem, $v1);
                }
            }
        }
        return $ret;
    }

}


class FieldSubstitutionProjectUpdateProcessor extends AbstractProjectUpdateProcessor{

    public function getFieldValue($fieldValue) {
        return $this->value;
    }
}

/**
 * Incrementa el valor en los campos especificados del archivo de datos de un proyecto
 */
class FieldIncrementProjectUpdateProcessor extends AbstractProjectUpdateProcessor {
    
    protected $dateFormat='Y-m-d';

    public function getFieldValue($fieldValue) {
        $ret  = $fieldValue;
        switch ($this->params['type']) {
            case 'loop':
                $ret = $this->incrementLoop($fieldValue, $this->value, $this->params["min"], $this->params["max"]);
                break;
            case 'data':
                $ret = $this->incrementData($fieldValue, $this->value, $this->params['unit']);
                break;
            default:
                break;
        }
        return $ret;
    }
    
    protected function incrementData($value, $inc, $unit){
        if(preg_match('/\d{1,2}[\/-]\d{1,2}[\/-]\d{4}/', $value)){
            $avalue = preg_split('/[\/-]/', $value);
            $value = $avalue[2]."/".$avalue[1]."/".$avalue[0];
        }
        $fecha = new DateTime($value);
        $fecha->add(new DateInterval('P'.$inc.$unit));
        return $fecha->format($this->dateFormat);        
    }
    
    protected function incrementLoop($value, $inc, $min, $max){
        $mod = $max-$min+1;
        return ($value+$min+$inc)%$mod+$min;
    }
}

class ArrayIncrementProjectUpdateProcessor extends FieldIncrementProjectUpdateProcessor{
    public function __construct() {
        $this->dateFormat="Y/m/d";
    }
    protected function runProcessField($field, &$projectMetaData) {
        ArrayFieldProjectUpdateProcessor::runProcessField($this, $field, $projectMetaData);
    }
}

