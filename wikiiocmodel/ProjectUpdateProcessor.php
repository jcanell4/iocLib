<?php
/**
 * ProjectUpdateProcessor: clases para la actualización de los datos de proyecto
 *                         a partir de los parámetros de configuración del tipo de proyecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

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
            $b = false;
            $data = &$projectMetaData;
            $akeys = explode("#", $field);
            $lim = count($akeys)-1;
            for($i=0; !$b && $i<$lim; $i++){
                $b = !isset($data[$akeys[$i]]);
                if(!$b){
                    $data = &$data[$akeys[$i]];
                }
            }
            if(!$b){
                $data[$akeys[$lim]]= self::_resolveUpdateValue($obj, $data[$akeys[$lim]]);
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
            if(is_array($keysOfArray) && array_diff_key($keysOfArray,array_keys(array_keys($keysOfArray)))){
                foreach ($keysOfArray[$field] as $arrayKey){
                    self::_runProcessField($obj, $field, $projectMetaData, $arrayKey);
                }            
            }else{
                foreach ($keysOfArray[$obj->getIdField()] as $arrayKey){
                    self::_runProcessField($obj, $field, $projectMetaData, $arrayKey);
                }
            }
         }
    }
    
    private static function _runProcessField($obj, $field, &$projectMetaData, $arrayKey){
        if(is_string($projectMetaData[$field])){
            $projectMetaData[$field] = json_decode($projectMetaData[$field], TRUE);
        }
        for ($i=0; $i<count($projectMetaData[$field]); $i++ ){
            $projectMetaData[$field][$i][$arrayKey] = $obj->getFieldValue($projectMetaData[$field][$i][$arrayKey]);
            if($obj->hasParam("concat")){
                $projectMetaData[$field][$i][$arrayKey] = $obj->concat($projectMetaData[$field][$i][$arrayKey], $obj->getParam("concat"));
            }
            if($obj->hasParam("returnType")){
                $projectMetaData[$field][$i][$arrayKey] = $obj->returnType($projectMetaData[$field][$i][$arrayKey], $obj->getParam("returnType"));
            }            
        }
    }
}


class FieldSubstitutionProjectUpdateProcessor extends AbstractProjectUpdateProcessor{
    /**
     * Modifica el conjunto de datos del archivo (meta.mdpr) de datos de un proyecto
     * @param string $value : valor que se utiliza en la substitución
     * @param array $params : conjunto de campos sobre los que se aplica la sustitución
     * @param array $projectMetaData : conjunto de datos del archivo meta.mdpr
     */
    public function getFieldValue($fieldValue) {
        return $this->value;
    }
}

/**
 * Incrementa el valor en los campos especificados del archivo de datos de un proyecto
 */
class FieldIncrementProjectUpdateProcessor extends AbstractProjectUpdateProcessor {
    protected $dateFormat='Y-m-d';
    /**
     * Modifica el conjunto de datos del archivo (meta.mdpr) de datos de un proyecto
     * @param string $value : valor que se utiliza para incrementar el valor del campo
     * @param array $params : array de campos [key, type, value] sobre los que se aplica el incremento
     * @param array $projectMetaData : conjunto de datos del archivo meta.mdpr
     */
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

