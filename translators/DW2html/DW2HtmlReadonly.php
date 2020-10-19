<?php
require_once "DW2HtmlParser.php";

class DW2HtmlReadonly extends DW2HtmlMarkup {

    public function isClosing($token) {


        // Aquest tipus només tanca quan es troba el seu token de tancament corresponent i el top és el mateix token



        return $token['type'] == $this->currentToken['type'] && $token['action'] == 'close'; //



//        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
//            || ($token['action'] === 'close' && $token['state'] == $this->currentToken['state']
//                && $token['type'] == $this->currentToken['type'])) {
//
//
//
//            return true;
//        } else {
//            return false;
//        }

    }
}