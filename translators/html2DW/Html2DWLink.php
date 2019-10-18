<?php
require_once "Html2DWParser.php";

class Html2DWLink extends Html2DWMarkup {


    protected function getContent($token) {
        // El token que arriba conté el text de l'enllaç
        $text = $token['value'];

        $url = $this->extractVarName($this->currentToken['raw'], 'href');

        $pos = strpos($url, 'doku.php?id='); // TODO: d'on obtenim aques valor?
        if ($pos !== false) {
            $queryPos = strpos($url, '=') +1;
            $url = substr($url, $queryPos);
        }


        if (strlen($text)>0) {
            $text = $url . '|' . $text;
        } else {
            $text = $url . '|' . $url;
        }

        return $this->getReplacement(self::OPEN) . $text;

    }

}