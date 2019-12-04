<?php
require_once "Html2DWParser.php";

class Html2DWLink extends Html2DWMarkup {

    protected function getSpecialContent($type, $token) {
        // No es tracta d'una URL si no d'una referencia
        $url = $this->extractVarName($token['raw'], 'title');

        switch ($type) {

            case 'table':

                return ':table:' . $url . ':';

            case 'figure':
                // TODO
                return 'TODO: enllaç de tipus figure';

            default:

                return 'ERROR: tipus d\'enllaç no reconegut ' . $type;

        }
    }


    protected function getContent($token) {

        $specialLink = $this->extractVarName($token['raw'], 'data-ioc-link', false);

        // Si es tracta d'un tipus especial
        if ($specialLink) {
            return $this->getSpecialContent($specialLink, $token);
        }


        try {
            $text = $this->extractVarName($token['raw'], 'title');
        } catch (Exception $e) {
            $text = "";
        }

        $url = '';

        try {
            $linkType = $this->extractVarName($token['raw'], 'data-dw-type');

            switch ($linkType) {
                case 'internal_link':
                    $url = $this->extractVarName($token['raw'], 'data-dw-ns');
                    break;

                case 'external_link':
                    $url = $this->extractVarName($token['raw'], 'href');
                    break;
            }
        } catch (Exception $e) {
            // Si no tenim la informació ho intentem deduir
            $url = $this->extractVarName($token['raw'], 'href');

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

        $return = $this->getReplacement(self::OPEN) . $text . $this->getReplacement(self::CLOSE);

        return $return;
    }


}