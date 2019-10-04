<?php


class Html2DWInstruction extends WiocclInstruction {

    // TODO: Extreure la superclasse de WiocclInstruction i fer un extend
    // Eliminar les funcions propies del Wioccl i les constants
//
//    public function parseToken($tokens, &$tokenIndex) {
//
//        return parent::parseToken($tokens, $tokenIndex);
//    }

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
