<?php

/**
 * Description of ValidateIfExistFieldAndHasValue
 *
 * @author professor
 */
class ValidateIfExistFieldAndHasValue extends ValidateWithResponse{
    /*
     * format de $data és: {responses:[conjunt de cmaps a comprovar], deniedResponse:BOOLEAN}
     */    
    public function validate($data) {
        $ret = true;
        foreach ($data["responses"] as $field){
            $ret = $ret && isset($this->response[$field]);
            if($ret && isset($this->response[$field]["type"])){
                $ret = $ret && !empty($this->response[$field]["value"]);
            }else{
                $ret = $ret && !empty($this->response[$field]);
            }
        }
        if($data["deniedResponse"]){
            $ret = !$ret;
        }        
        return $ret;
    }

}
