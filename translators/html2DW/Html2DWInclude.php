<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWInclude extends Html2DWMarkup {

    protected function getContent($token) {

        try {
            $value = $this->extractVarName($this->currentToken['raw'], 'data-dw-include');
            $type = $this->extractVarName($this->currentToken['raw'], 'data-dw-include-type');
        } catch (Exception $e) {
            $value = "Error";
            $type = "Error";
        }

        return "::include:\n{{{$type}>{$value}}}\n:::";
    }
}
