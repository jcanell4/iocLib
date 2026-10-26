<?php
require_once "WiocclParser.php";

class WiocclReSet extends WiocclSet {

    const PREFIX = '$$';


    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL) {
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction, TRUE);
    }

//    public function updateParentArray($fromType, $key){
//        self::stc_updateParentArray($this, $fromType, $key);
//    }
}