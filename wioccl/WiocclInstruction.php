<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';


class WiocclInstruction extends IocInstruction
{
    const FROM_CASE = "fromCase";
    const FROM_RESET = "fromReset";
    const FROM_REPARSESET = "fromReparseset";

    protected $rawValue;
    protected $fullInstruction="";
    protected $parentInstruction=NULL;
    protected $updatablePrefix="";
    
    protected $dataSource = [];

    protected $arrays = [];
    
    protected $resetables = null;

    protected static $parserClass = "WiocclParser";

    // TODO: Afegir dataSource al constructor, deixem els arrays separats perque el seu us es intern, al datasource es ficaran com a JSON
    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL){
        $this->rawValue = $value;
        $this->arrays += $arrays;
        $this->dataSource = $dataSource;
        $this->parentInstruction = $parentInstruction;
        if($resetables==NULL){
            $this->resetables = new WiocclResetableData();
        }else{
            $this->resetables = $resetables;
        }
    }
    
    public function updateParentArray($fromType, $key=NULL){
        self::stc_updateParentArray($this, $fromType, $key);
    }
    
    public static function stc_updateParentArray(&$obj, $fromType, $key=NULL){
        if($obj->parentInstruction!=NULL){
            if($key===NULL){
                $obj->parentInstruction->arrays = array_merge($obj->parentInstruction->arrays, $obj->arrays);
            }else if(isset ($obj->arrays[$key])){
                $obj->parentInstruction->arrays[$key] = $obj->arrays[$key];
            }else if(isset($obj->parentInstruction->arrays[$key])){
                unset($obj->parentInstruction->arrays[$key]);
            }
            $obj->parentInstruction->updateParentArray($fromType, $key);
        }
    }

    protected function isClosingTagExcluded($type) {
        $class = static::$parserClass;
        return in_array($type, $class::getExcludedClosingTags());
    }

}
