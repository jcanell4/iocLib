<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlInstruction extends IocInstruction {

    protected static $parserClass = "DW2HtmlParser";

//    protected $extra;
//
//
//    protected function setExtra($extraData) {
//        $this->extra = $extraData;
//    }
//
//    protected function getClassForToken($token)
//    {
//        $instance = new $token['class']($token['value'], $this->getArrays(), $this->getDataSource(), $this->resetables, $this);
//        $instance->setExtra($token['extra']);
//        return $instance;
//    }
}
