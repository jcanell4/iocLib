<?php

abstract class WiocclUpdatableInstruction extends WiocclInstruction{
    protected $newKeyValue=[];
    protected $updated = FALSE;

    // TODO: Afegir dataSource al constructor, deixem els arrays separats perque el seu us es intern, al datasource es ficaran com a JSON
    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);
        $this->updatable=TRUE;        
    }
       
    public function update($rightValue, $result=""){
        if($rightValue){
            if(!$this->updated){
                $this->updateData($rightValue, $result);
                $this->arrays[$this->newKeyValue["key"]] = $this->newKeyValue["value"];
                $this->updateParentArray($this->updatablePrefix, $this->newKeyValue["key"]);
        }
        }else if(isset($this->arrays[$this->newKeyValue["key"]])){ 
            unset($this->arrays[$this->newKeyValue["key"]]);
            $this->updateParentArray($this->updatablePrefix, $this->newKeyValue["key"]);
        }
        $this->updated = true;
    }
    
    protected abstract function updateData($rightValue, $result="");
    
    public function isUpdated(){
        return $this->updated;
    }
}
