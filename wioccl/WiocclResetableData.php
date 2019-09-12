<?php

class WiocclResetableData{
    private $arrays = array();
    
    public function __construct($resetableDate=NULL) {
        if($resetableDate !==NULL){
            $this->arrays += $resetableDate->arrays ;
        }
        $this->arrays[]=array();
    }
    
    public function getValue($key){
        $pos=count($this->arrays)-1;
        $isset = false;
        $value=NULL;
        While(!$isset && $pos>=0){
            if(is_array($key)){
                $item =& $this->arrays[$pos];
                for($i=0; $i<count($key)-1; $i++){
                    $item = & $item[$key[$i]];
                } 
                $isset = isset($item[$key[count($key)-1]]);         
                if($isset){
                    $value = $item[$key[count($key)-1]];
                }
            }else{
                $isset = isset($this->arrays[$pos][$key]);
                if($isset){
                    $value = $this->arrays[$pos][$key];
                }
            }
            if($value===NULL){
                $pos--;
            }
        }
        return $value;
    }
    
    public function setValue($key, $value){
        if(is_array($key)){
            $item =& $this->arrays[count($this->arrays)-1];
            for($i=0; $i<count($key)-1; $i++){
                if(!isset($item[$key[$i]])){
                    $item[$key[$i]] = array();
                }
                $item = & $item[$key[$i]];
            } 
            $item[$key[count($key)-1]] = $value;
        }else{
            $this->arrays[count($this->arrays)-1][$key]=$value;
        }        
    }
    
    public function addValueToArrayItem($key, $value){
        if(!isset($this->arrays[count($this->arrays)-1][$key])
                && ($e = $this->getValue($key))!=NULL){
            $this->arrays[count($this->arrays)-1][$key]=$e;
        }
        $this->arrays[count($this->arrays)-1][$key][] = $value;  
    }
    
    public function issetKey($key){
        $ret=FALSE;
        $pos=count($this->arrays)-1;
        $isset = false;
        if($pos>-1){
            if(is_array($key)){
                While(!$isset && $pos>=0){
                    $item =& $this->arrays[$pos];
                    for($i=0; $i<count($key)-1; $i++){
                        $item = & $item[$key[$i]];
                    } 
                    $isset = isset($item[$key[count($key)-1]]);
                    $pos--;
                }
                $ret = $isset;
            }else{
                While(!$isset && $pos>=0){
                    $isset = isset($this->arrays[$pos][$key]);
                    $pos--;
                }
                $ret = $isset;
            }
        }
        return $ret;
    }
    
    public function unsetKey($key){
        $item =& $this->arrays[count($this->arrays)-1];
        if(is_array($key)){
            for($i=0; $i<count($key)-1; $i++){
                $item = & $item[$key[$i]];
            } 
            unset($item[$key[count($key)-1]]);
        }else{
            unset($this->arrays[count($this->arrays)-1][$key]);
        }
    }
    
    public function addNewContext(){
        array_push($this->arrays, array());
    }

    public function RemoveLastContext($saveData=FALSE){
        $ctx = array_pop($this->arrays);
        if($saveData){
            $this->updateData($ctx);
        }
        return $ctx;
    }
    
    public function updateData($ctx){
        foreach ($ctx as $key => $value) {
            $this->setValue($key, $value);
        }        
    }
}
