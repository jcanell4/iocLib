<?php
require_once "Html2DWParser.php";

class Html2DWLink extends Html2DWMarkup {


    protected function getContent($token) {

        // El token que arriba conté el text de l'enllaç

        $text = $token['value'];


        // Al current token es troba el raw amb tota la informació, cal extreure:
        // TODO: el valor del href

        $pattern = '/href="(.*?)"/';
        preg_match($pattern, $this->currentToken['raw'], $matches);
        $url= $matches[1];

        if (strlen($text)>0) {
            $text = $url . '|' . $text;
        } else {
            $text = $url . '|' . $url;
        }

        return $this->getReplacement(self::OPEN) . $text;

    }

}