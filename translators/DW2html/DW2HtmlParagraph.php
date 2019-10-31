<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlParagraph extends DW2HtmlBlock {

    protected $closed = FALSE;

    protected function resolveOnClose($field) {

//        if ($this->closed) {
//            return '';
//        }


        $value = $field . $this->getReplacement(self::CLOSE);

        $this->closed = TRUE;
//        var_dump(static::$stack, $this->currentToken);
//        die();




        return $value;
//        return $field . $this->getReplacement(self::CLOSE);
    }

    protected function getReplacement($position) {

        if ($this->closed && $position == self::CLOSE) {
            return '';
        }

        if (static::DEBUG_MODE) {
            return $this->getDebugReplacement($position);
        } else {
            return is_array($this->extra['replacement']) ? $this->extra['replacement'][$position] : $this->extra['replacement'];
        }
    }



//    protected $newLinefound = '';
//
//    protected function getReplacement($position) {
//
//        if (count(static::$stack) == 0) {
//
//            $ret = parent::getReplacement($position);
//
//            if ($position === IocInstruction::CLOSE) {
//                $ret .= $this->newLinefound;
//            }
//        } else {
//            $ret = '';
//        }
//
//
//
//        return $ret;
//    }


    protected function getContent($token) {
//        $pattern = "/\\n$/";

//            var_dump($this->currentToken);
//            die();

//        if (preg_match($token['pattern'], trim($token['raw']))) {
//            $token['value'] = substr($token['value'], 0, strlen($token['value']) - 1);
//        }

        $value = trim($token['raw']);

        return $this->getReplacement(self::OPEN) . $value;
//        var_dump($token);
//        die();



//        if (preg_match($pattern, $token['value'])) {
//            $this->newLinefound = "\n";
//            $token['value'] = substr($token['value'], 0, strlen($token['value']) - 1);
//        }


//        if (preg_match($pattern, $token['value'])) {
//            $this->newLinefound = "\n";
//            $token['value'] = substr($token['value'], 0, strlen($token['value']) - 1);
//        }

        // Un block només pot ser un paràgraf sí l'stack es buit (TODO: o l'element superior de l'stack es un comentari)
//        if (count(static::$stack) == 0) {
//            return $this->getReplacement(self::OPEN) . $token['value'];
//        }

//        var_dump($token['value'], count(static::$stack));

//        return $this->getReplacement(self::OPEN) . $token['value'];
    }
}
