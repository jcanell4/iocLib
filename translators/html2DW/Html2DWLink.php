<?php
require_once "Html2DWParser.php";

class Html2DWLink extends Html2DWMarkup {


    protected function getContent($token) {
        // El token que arriba conté el text de l'enllaç
        $text = $token['value'];

        $pattern = '/href="(.*?)"/';
        preg_match($pattern, $this->currentToken['raw'], $matches);
        $url= $matches[1];

        $pos = strpos($url, 'doku.php?id='); // TODO: cal extreure la baseUrl?
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