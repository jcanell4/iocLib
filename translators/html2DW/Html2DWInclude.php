<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWInclude extends Html2DWMarkup {

    protected function getContent($token) {

        try {
            $value = $this->extractVarName($this->currentToken['raw'], 'data-dw-include');
            $type = $this->extractVarName($this->currentToken['raw'], 'data-dw-include-type');

            // Si no està ressaltat es llençarà la excepció
            $highlighted = $this->extractVarName($this->currentToken['raw'], 'data-dw-highlighted') == "true";
        } catch (Exception $e) {
            $value = $value ? $value : "Error";
            $type = $type ? $type : "Error";

            $highlighted = FALSE;
        }

        if ($highlighted) {
            return "::include:\n{{{$type}>{$value}}}\n:::";
        } else {
            return "{{{$type}>{$value}}}\n";
        }

    }
}
