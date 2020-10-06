<?php
require_once "WiocclParser.php";

class WiocclField extends WiocclInstruction {
            
    protected function getContent (&$token) {
        return $token['value'];
    }
    
    protected function resolveOnClose ($field, $token) {
        $ret = '[ERROR: undefined field]';
        // es un array? el value tindrà el format xxx['yyy'] llavors el valor serà $this->arrays[xxx][yyy]

        if (preg_match ('/(.*?)\[(.*?)\]/', $field, $matches)===1) {
            // es un array
            $varName = $matches[1];
            $key = $matches[2];

            // Provem a fer la conversió de les dades en arrays si no ho son
            if (isset($this->dataSource[$varName]) && !is_array($this->dataSource[$varName])) {
                $this->dataSource[$varName] = json_decode($this->dataSource[$varName], true);
            }

            if ($this->resetables->issetKey($varName)) {
                $valueOfKey = $this->resetables->getValue($varName);
                if (!is_array($valueOfKey)) {
                    $this->resetables->setValue($varName, json_decode($valueOfKey, true));
                }
                if ($this->resetables->issetKey([$varName, $key])) {
                    $ret =$this->resetables->getValue([$varName,$key]);
                }
            }else if (isset($this->arrays[$varName])) {
                if (!is_array($this->arrays[$varName])) {
                    $this->arrays[$varName] = json_decode($this->arrays[$varName], true);
                }
                if (isset($this->arrays[$varName][$key])){
                    $ret =$this->arrays[$varName][$key];
                }
            } else if (isset($this->dataSource[$varName])) {
                if (!is_array($this->dataSource[$varName])) {
                    $this->dataSource[$varName] = json_decode($this->dataSource[$varName], true);
                }
                if (isset($this->dataSource[$varName][$key])) {
                    $ret =$this->dataSource[$varName][$key];
                }
            }else{
                $ret=NULL;
            }
            if(strlen($field)> strlen($matches[0])){
                $this->arrays["_TMP_"]=$ret;
                $newkey = substr($field, strlen($matches[0]));
                $newToken = ["state"=>"content","value"=>"_TMP_$newkey"];
                $ret = $this->getContent($newToken);
                unset($this->arrays["_TMP_"]);
            }
        } else {
            $fieldName = $field;

            // Primer comprovem als resetables i si no es troba comprovem a l'arrays
            if ($this->resetables->issetKey($fieldName)) {
                $ret = $this->resetables->getValue($fieldName);
            // despés comprovem als arrays i si no es troba comprovem el datasource
            }else if(isset($this->arrays[$fieldName])) {
//                $ret =json_encode($this->arrays[$fieldName]);
                $ret =$this->arrays[$fieldName];
            } else if (isset($this->dataSource[$fieldName])) {
                $ret =$this->dataSource[$fieldName];
            // Si no està en lloc, potser es tracti d'un nom de camp compost 
            }else if(strpos($fieldName, "#")>0){
                $akeys = explode("#", $fieldName);
                $ret = $this->dataSource;
                for ($i=0; $i<count($akeys); $i++) {
                    $ret = $ret[$akeys[$i]];
                }
            }
        }

        if(!is_string($ret)){
            $ret = json_encode($ret);
        }

        // Codi per afegir la estructura
        $class = (static::$parserClass);
        $class::close();
        $this->item->result  = $ret;

        // Reconstruim el codi original
        $this->rebuildRawValue($this->item, $this->currentToken['tokenIndex'], $token['tokenIndex']);

        return $ret;

    }    
}