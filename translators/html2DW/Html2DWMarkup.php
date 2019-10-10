<?php
require_once "Html2DWParser.php";

class Html2DWMarkup extends Html2DWInstruction {

    const OPEN = 0;
    const CLOSE = 1;

    protected function getReplacement($position) {

        if (is_array($this->extra['replacement'])) {
//            echo "Es un array, element a la segona posició:" . $this->extra['replacement'][self::CLOSE] . "\n";
//            var_dump($this->extra['replacement']);
        }


        return is_array($this->extra['replacement']) ? $this->extra['replacement'][$position] : $this->extra['replacement'];
    }

    protected function getContent($token) {
        return $this->getReplacement(self::OPEN) . $token['value'];
    }

    protected function resolveOnClose($field) {
//        echo "*** $field: " . $field;


        $r = $this->getReplacement(self::CLOSE);
//        echo '>>> OnClose: ' . $r . "\n";


        return $field . $r;
        //return $field . $this->getReplacement(self::CLOSE);
    }
}