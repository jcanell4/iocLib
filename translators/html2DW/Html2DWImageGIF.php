<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWImageGIF extends Html2DWImage {

    protected function getContent($token) {

        try {
            $value = $this->extractVarName($this->currentToken['raw'], 'data-dw-ns');
        } catch (Exception $e) {
            $value = "Error";
        }

        $value .= '|';

        try {
            $alt = $this->extractVarName($this->currentToken['raw'], 'alt');
            $value .= $alt;

        } catch (Exception $e) {
            // totes les imatges han de contenir alt, però si no es trobes no es greu
        }

        return '{{iocgif>' . $value . '}}';
    }


}
