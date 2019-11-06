<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlParagraph extends DW2HtmlBlock {

    protected $closed = FALSE;

    protected $addNewLines = 0;

    protected function resolveOnClose($field) {

//        if ($this->closed) {
//            return '';
//        }




        $value =  $field . str_repeat("\n", $this->addNewLines) . $this->getReplacement(self::CLOSE);

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

//        $value = trim($token['raw']); // ALERTA TODO: si no fem un trim es provoca un bucle infinit
        $value = $token['raw'];


        // Recorrem la cadena desdel final fins al principi
        $counter = 0;

        for ($i = strlen($value)-1; $i>=0; $i--) {
            if (substr($value, $i, 1) === "\n") {
                $counter++;
            } else {
                break;
            }
        }


//        var_dump($value, $counter);

//        die ();
//        $pattern = "/(\n*)$/ms";
//        preg_match_all($pattern, $value, $matches, PREG_OFFSET_CAPTURE);
//
//        $len = 0;
//
//        if (count($matches[1])>0) {
//
//            var_dump($value, $matches);
//            die();
//            $len = count($matches[0]);
//        }
//
//
        $value = substr($value, 0, strlen($value)-$counter);

        $this->addNewLines = $counter;



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
