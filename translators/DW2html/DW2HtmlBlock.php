<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlBlock extends DW2HtmlMarkup {

    protected $newLinefound = '';

    protected function getReplacement($position) {

        if (count(static::$stack) == 0) {

            $ret = parent::getReplacement($position);

            if ($position === IocInstruction::CLOSE) {
                $ret .= $this->newLinefound;
            }
        } else {
            $ret = '';
        }



        return $ret;
    }


    protected function getContent($token) {
        $pattern = "/\\n$/";

        if (preg_match($pattern, $token['value'])) {
            $this->newLinefound = "\n";
            $token['value'] = substr($token['value'], 0, strlen($token['value']) - 1);
        }

        // Un block només pot ser un paràgraf sí l'stack es buit (TODO: o l'element superior de l'stack es un comentari)
        if (count(static::$stack) == 0) {
            return $this->getReplacement(self::OPEN) . $token['value'];
        }

//        var_dump($token['value'], count(static::$stack));

        return $this->getReplacement(self::OPEN) . $token['value'];
    }
}
