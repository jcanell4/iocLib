<?php
require_once "WiocclParser.php";

class WiocclField extends WiocclInstruction {

    protected function getContent ($token) {
        $ret = '[ERROR: undefined field]';
        // es un array? el value tindrà el format xxx['yyy'] llavors el valor serà $this->arrays[xxx][yyy]

        if (preg_match ('/(.*?)\[(.*?)\]/', $token['value'], $matches)===1) {
            // es un array
            $varName = $matches[1];
            $key = $matches[2];

            // Provem a fer la conversió de les dades en arrays si no ho son
            if (!is_array($this->dataSource[$varName])) {
                $this->dataSource[$varName] = json_decode($this->dataSource[$varName], true);
            }

            if (!is_array($this->arrays[$varName])) {
                $this->arrays[$varName] = json_decode($this->arrays[$varName], true);
            }


            if (isset($this->arrays[$varName][$key])) {
                $ret =$this->arrays[$varName][$key];
            } else if (isset($this->dataSource[$varName][$key])) {

                $ret =$this->dataSource[$varName][$key];

            }else{
                $ret=NULL;
            }
            if(strlen($token['value'])> strlen($matches[0])){
                $this->arrays["_TMP_"]=$ret;
                $newkey = substr($token['value'], strlen($matches[0]));
                $ret = $this->getContent(["state"=>"content","value"=>"_TMP_$newkey"]);
                unset($this->arrays["_TMP_"]);
            }
        } else {
            $fieldName = $token['value'];

            // Primer comprovem als arrays i si no es troba comprovem el datasource
            if (isset($this->arrays[$fieldName])) {
//                $ret =json_encode($this->arrays[$fieldName]);
                $ret =$this->arrays[$fieldName];
            } else if (isset($this->dataSource[$fieldName])) {
                $ret =$this->dataSource[$fieldName];
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
        return $ret;

    }
    
//    public function parseTokens($tokens, &$tokenIndex)
//    {
//
//        $result = '';
//
//
//        while ($tokenIndex<count($tokens)) {
//
//            $parsedValue = $this->parseToken($tokens, $tokenIndex);
//
//            if ($parsedValue === null) { // tancament del field
//                break;
//
//            } else {
//                $result .= $parsedValue;
//            }
//
//            ++$tokenIndex;
//        }
//
//        return $result;
//    }
}