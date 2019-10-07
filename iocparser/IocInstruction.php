<?php

class IocInstruction
{
//    const FROM_CASE = "fromCase";
//    const FROM_RESET = "fromReset";
//    const FROM_REPARSESET = "fromReparseset";
//
    protected $rawValue;
//    protected $fullInstruction="";
    protected static $instancesCounter=0;
//    protected $parentInstruction=NULL;
//    protected $updatablePrefix="";
//
////    // TODO: El datasource es passarà al constructor del parser desde la wiki
//    protected $dataSource = [];
//
//    protected $arrays = [];
//    
//    protected $resetables = null;
    
    // TODO: Afegir dataSource al constructor, deixem els arrays separats perque el seu us es intern, al datasource es ficaran com a JSON
    public function __construct($value = null/*, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL*/){
        $this->rawValue = $value;
//        $this->arrays += $arrays;
//        $this->dataSource = $dataSource; // TODO: Reactivar quan es comprovi que funciona
//        $this->parentInstruction = $parentInstruction;
//        if($resetables==NULL){
//            $this->resetables = new WiocclResetableData();
//        }else{
//            $this->resetables = $resetables;
//        }
    }
    
//    public function updateParentArray($fromType, $key=NULL){
//        self::stc_updateParentArray($this, $fromType, $key);
//    }
    
//    public static function stc_updateParentArray(&$obj, $fromType, $key=NULL){
//        if($obj->parentInstruction!=NULL){
//            if($key===NULL){
//                $obj->parentInstruction->arrays = array_merge($obj->parentInstruction->arrays, $obj->arrays);
//            }else if(isset ($obj->arrays[$key])){
//                $obj->parentInstruction->arrays[$key] = $obj->arrays[$key];
//            }else if(isset($obj->parentInstruction->arrays[$key])){
//                unset($obj->parentInstruction->arrays[$key]);
//            }
//            $obj->parentInstruction->updateParentArray($fromType, $key);
//        }
//    }
    
    protected function resolveOnClose($result){
        return $result;
    }

    public function getTokensValue($tokens, &$tokenIndex)
    {
        return $this->parseTokens($tokens, $tokenIndex);
    }

    protected function getContent($token)
    {
        return $token['value'];
    }

    public function parseTokens($tokens, &$tokenIndex = 0)
    {

        $result = '';

        while ($tokenIndex < count($tokens)) {
            $newChunk = $this->parseToken($tokens, $tokenIndex);
            if ($newChunk === null) { // tancament del wiocclXXX
                break;
            }else{
                $result .= $newChunk ;
            }
            ++$tokenIndex;
        }
        
        return $this->resolveOnClose($result);
    }

    // l'index del token analitzat s'actualitza globalment per referència
    public function parseToken($tokens, &$tokenIndex)
    {

        $currentToken = $tokens[$tokenIndex];
        $result = '';

        if ($currentToken['state'] == 'content') {
            $action = 'content';
        } else {
            $action = $currentToken['action'];
        }


        switch ($action) {
            case 'content':
                    $result .= $this->getContent($currentToken);
                break;

            case 'open':
                $mark = self::$instancesCounter==0;
                self::$instancesCounter++;
                $item = $this->getClassForToken($currentToken);

                if($mark){
                    $result .= $item->getTokensValue($tokens, ++$tokenIndex);
//                    $result .= "<mark title='${$this->fullInstruction}'>".$item->getTokensValue($tokens, ++$tokenIndex)."</mark>";
                }else{
                    $result .= $item->getTokensValue($tokens, ++$tokenIndex);
                }
                
                self::$instancesCounter--;
                break;

            case 'close':
                return null;
                break;
        }

        return $result;
    }

    protected function getClassForToken($token)
    {
        // TODO: pasar el datasource i els arrays al constructor
        return new $token['class']($token['value'], $this->getArrays(), $this->getDataSource(), $this->resetables, $this);
    }

    protected function normalizeArg($arg)
    {
        if (strtolower($arg) == 'true') {
            return true;
        } else if (strtolower($arg) == 'false') {
            return false;
        } else if (is_int($arg)) {
            return intval($arg);
        } else if (is_numeric($arg)) {
            return floatval($arg);
        } else if (preg_match("/^''(.*?)''$/", $arg, $matches) === 1) {
            return $this->normalizeArg($matches[1]);
        } else {
            return $arg;
        }

    }

    protected function extractNumber($value, $attr, $mandatory=true) {
        $ret = 0;
        if (preg_match('/'.$attr.'="(.*?)"/', $value, $matches)) {
//            $ret = (new IocParser($matches[1], $this->getArrays(), $this->getDataSource()))->getValue();
            $ret = IocParser::getValue($matches[1], $this->getArrays(), $this->getDataSource(), $this->getResetables());
        } else if($mandatory){
            throw new Exception("$attr is missing");
        }
        if (is_numeric($ret)) {
            $ret = intval($ret);
        }
        return $ret;
    }
    
    protected function extractVarName($value, $attr="var", $mandatory=true) {
        if (preg_match('/'.$attr.'="(.*?)"/', $value, $matches)) {
            return $matches[1];
        } else if($mandatory){
            throw new Exception("$attr name is missing");
        }
        return "";
    }

    protected function extractArray($value, $attr="array", $mandatory=true) {
        $jsonString = '[]';
        // ALERTA: El $value pot ser un json directament o una variable, s'ha de fer un parse del $value
        if (preg_match('/'.$attr.'="(.*?)"/', $value, $matches)) {
//            $jsonString = (new IocParser($matches[1], $this->getArrays(), $this->getDataSource()))->getValue();
            $string = preg_replace("/''/", '"', $matches[1]);
            $jsonString = IocParser::getValue($string, $this->getArrays(), $this->getDataSource(), $this->getResetables());
        } else if($mandatory){
            throw new Exception("Array is missing");
        }
        return json_decode($jsonString, true);
    }

    protected function extractMap($value, $attr="map", $mandatory=true) {
        $jsonString = '{}';
        // ALERTA: El $value pot ser un json directament o una variable, s'ha de fer un parse del $value
        if (preg_match('/'.$attr.'="(.*?)"/', $value, $matches)) {
//            $jsonString = (new IocParser($matches[1], $this->getArrays(), $this->getDataSource()))->getValue();
            $string = preg_replace("/''/", '"', $matches[1]);
            $jsonString = IocParser::getValue($string, $this->getArrays(), $this->getDataSource(), $this->getResetables());
        } else if($mandatory){
            throw new Exception("Map is missing");
        }
        return json_decode($jsonString, true);
    }

    public function getResetables(){
        return $this->resetables;
    }

    public function getDataSource(){
        return $this->dataSource;
    }

    public function setArrayValue($key, $value){
        $this->arrays[$key] = $value;
    }
    
    public function getArrays(){
        return $this->arrays;
    }
    
    public function getRawValue(){
        return $this->rawValue;
    }

    public function update($rightValue, $result=""){
        throw new Exception("This class is not updatable");
    }
}
