<?php
require_once "WiocclParser.php";

class WiocclExtra extends WiocclField{

    protected function resolveOnClose ($key) {
        $ret = '[ERROR: undefined extra data]';
        // es un array? el value tindrà el format xxx['yyy'] llavors el valor serà $this->arrays[xxx][yyy]

        $fieldName = $key;
        if(isset($this->dataSource["dadesExtres"])){
            $ret = $this->_getExtraValue(json_decode($this->dataSource["dadesExtres"], true), $fieldName, $ret);
        }

        if(!is_string($ret)){
            $ret = json_encode($ret);
        }
        return $ret;

    }
    
    private function _getExtraValue($dadesExtres, $fieldName, $default){
        $ret;
        $found = -1;
        for($i=0; $found==-1 && $i<count($dadesExtres); $i++){
            if($dadesExtres[$i]["nom"] === $fieldName){
                $found = $i;
            }
        }
        if($found==-1){
            $ret = $default;
        }else{
            switch ($dadesExtres[$found]["tipus"]){
                case "dada":
                    $ret = $this->_getExtraDataValue($dadesExtres[$found]["valor"]);
                    break;
            }
        }
        return $ret;
    }
    
    private function _getExtraDataValue($value){
        return $value;
    }    
}
