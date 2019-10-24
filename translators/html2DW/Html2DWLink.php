<?php
require_once "Html2DWParser.php";

class Html2DWLink extends Html2DWMarkup {


    protected function getContent($token) {
        // El token que arriba conté el text de l'enllaç
        $text = $token['value'];
        $url = '';

        try {
            $linkType = $this->extractVarName($this->currentToken['raw'], 'data-dw-type');

            switch ($linkType) {
                case 'internal_link':
                    $url = $this->extractVarName($this->currentToken['raw'], 'data-dw-ns');
                    break;

                case 'external_link':
                    $url = $this->extractVarName($this->currentToken['raw'], 'href');
                    break;
            }
        } catch (Exception $e) {
            // Si no tenim la informació ho intentem deduir
            $url = $this->extractVarName($this->currentToken['raw'], 'href');

            $pos = strpos($url, 'doku.php?id=');
            if ($pos !== false) {
                $queryPos = strpos($url, '=') + 1;
                $url = substr($url, $queryPos);
            }
        }

        if (strlen($text) > 0) {
            $text = $url . '|' . $text;
        } else {
            $text = $url . '|' . $url;
        }

        return $this->getReplacement(self::OPEN) . $text;

    }

}