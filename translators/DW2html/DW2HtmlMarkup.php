<?php
require_once "DW2HtmlParser.php";

class DW2HtmlMarkup extends Html2DWInstruction {



    protected function getContent($token) {
        $value = $token['value'];




        if (isset($token['extra']) && $token['extra']['remove-new-line'] === TRUE) {
            $value = str_replace("\n", '', $value);
        }

//        if ($this->currentToken['state'] == 'paragraph') {
//            var_dump($token);
//            die();
//        }

        return $this->getReplacement(self::OPEN) . $value;
    }

    protected function resolveOnClose($field) {

//        if ($this->currentToken['state'] == 'paragraph') {
//            var_dump($field);
//            die();
//        }


        return $field . $this->getReplacement(self::CLOSE);
    }
}