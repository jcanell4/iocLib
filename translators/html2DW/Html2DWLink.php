<?php
require_once "Html2DWParser.php";

class Html2DWLink extends Html2DWMarkup {


    protected function getContent($token) {

        // El token que arriba conté el text de l'enllaç

        $text = $token['value'];


        // Al current token es troba el raw amb tota la informació, cal extreure:
        // TODO: el valor del href
        // TODO: anchor

//        var_dump($token,$this->currentToken);
//        die();

        return $this->getReplacement(self::OPEN) . $text;

    }

}