<?php

/**
 * Description of ValidateByExactMatch
 *
 * @author professor
 */
class ValidateByExactMatch extends ValidateWithResponse{
    /*
     * format de $data és: {responses:[conjunt de valors de les responstes com a parells clau-valor (exemple:{key:"key1", value:"value1"})], invertedResponse:BOOLEAN}
     */    
    public function validate($data) {
        $ret = true;
        foreach ($data["responses"] as $matcher){
            $ret = $ret && isset($this->response[$matcher["key"]]);
            if($ret && isset($this->response[$matcher["key"]]["type"])){
                $ret = $ret && ($this->response[$matcher["key"]]["value"]===$matcher["value"]);
            }else{
                $ret = $ret && ($this->response[$matcher["key"]]===$matcher["value"]);
            }
        }
        if($data["deniedResponse"]){
            $ret = !$ret;
        }        
        return $ret;
    }

}
