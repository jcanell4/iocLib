<?php

require_once "../../IocParser/IocInstruction.php";

class Html2DWInstruction extends IocInstruction {

    protected $extra;


    protected function setExtra($extraData) {
        $this->extra = $extraData;
    }

    protected function getClassForToken($token)
    {
        $instance = new $token['class']($token['value'], $this->getArrays(), $this->getDataSource(), $this->resetables, $this);
        $instance->setExtra($token['extra']);
        return $instance;
    }
}
