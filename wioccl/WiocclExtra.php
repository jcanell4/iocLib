<?php
require_once "WiocclParser.php";

class WiocclExtra extends WiocclField{

    protected function resolveOnClose ($result, $tokenEnd) {
        $ret = '[ERROR: undefined extra data]';
        // es un array? el value tindrà el format xxx['yyy'] llavors el valor serà $this->arrays[xxx][yyy]

        $fieldName = $result;
        if(isset($this->dataSource["dadesExtres"])){
            $ret = $this->_getExtraValue(json_decode($this->dataSource["dadesExtres"], true), $fieldName, $ret);
        }

        if(!is_string($ret)){
            $ret = json_encode($ret);
        }

        $this->close($ret, $tokenEnd);

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

    // ALERTA! No està comprovat, això és només per generar la estructura. Copiat de WiocclField
    protected function splitOpeningAttrs(&$tag, &$attrs) {
        // el nom del camp es troba com atribut
        $tag .= "%s";
    }

    // ALERTA! No està comprovat, això és només per generar la estructura. Copiat de WiocclField
    protected function close(&$result, $tokenEnd) {

        parent::close($result, $tokenEnd);

        // Codi per afegir la estructura
        $this->generateRawValue($this->item->attrs, $this->currentToken['tokenIndex']+1, $tokenEnd['tokenIndex']-1);

    }
}
