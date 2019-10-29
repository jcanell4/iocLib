<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlCode extends DW2HtmlMarkup {

    protected $newLinefound = '';

    protected function getReplacement($position) {

        $ret = parent::getReplacement($position);

        if ($position === IocInstruction::CLOSE) {
            $ret .= $this->newLinefound;
        }

        return $ret;
    }


    protected function getContent($token) {

        // El contingut dintre d'aquest block no parseja, es deixa tal qual

        if (preg_match($token['pattern'], $token['raw'], $match)) {
            $value = $match[1];
        } else {
            $value = "ERROR: No s'ha trobat coincidencia amb el patró";
        }

        $value = $this->getReplacement(self::OPEN) . $value . $this->getReplacement(self::CLOSE);

        return $value;
    }
}
