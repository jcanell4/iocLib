<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlBlock extends DW2HtmlMarkup {

    protected $newLinefound = '';

    protected function getReplacement($position) {

        $ret = parent::getReplacement($position);

        if ($position === IocInstruction::CLOSE) {
            $ret .= $this->newLinefound;
        }

        return $ret;
    }


    protected function getContent($token) {
        $pattern = "/\\n$/";

        if (preg_match($pattern, $token['value'])) {
            $this->newLinefound = "\n";
            $token['value'] = substr($token['value'], 0, strlen($token['value']) - 1);
        }

        return $this->getReplacement(self::OPEN) . $token['value'];
    }
}
