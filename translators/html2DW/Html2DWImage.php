<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWImage extends Html2DWMarkup {

    protected function getContent($token) {

        // 1 . obtenir la url
        //  1.1 és intern o extern?

        $value = 0;


        try {
            $linkType = $this->extractVarName($this->currentToken['raw'], 'data-dw-type');

            switch ($linkType) {
                case 'internal_image':
                    $value = $this->extractVarName($this->currentToken['raw'], 'data-dw-ns');
                    break;

                case 'external_image':
                    $value = $this->extractVarName($this->currentToken['raw'], 'src');
                    break;
            }
        } catch (Exception $e) {
            // Si no tenim la informació ho intentem deduir
            $value = $this->extractVarName($this->currentToken['raw'], 'src');

            $pos = strpos($value, 'fetch.php?media=');
            if ($pos !== false) {
                $queryPos = strpos($value, '=') + 1;
                $value = substr($value, $queryPos);
            }
        }

        // Ajustem la mida
        $size = '';

        try {
            $width = $this->extractVarName($this->currentToken['raw'], 'width');

            // només pot haver height si hi ha width (funcionament de Dokuwiki)

            $size .= '?' . $width;

            try {
                $height = $this->extractVarName($this->currentToken['raw'], 'height');

                $size .= 'x'.$height;

            } catch (Exception $e) {
                // No cal fer res, només s'afegeix l'amplada
            }

        } catch (Exception $e) {
            // No cal fer res, és l'alineació per defecte
        }

        $value .= $size;


        // Ajustem l'alineament
        try {
            $CSSClasses = $this->extractVarName($this->currentToken['raw'], 'class');


            if (strpos($CSSClasses, 'mediacenter') !== false) {

                $value = ' ' . $value . ' ';
            } else if (strpos($CSSClasses, 'medialeft') !== false) {
                $value = ' ' . $value;

            } else if (strpos($CSSClasses, 'mediaright') !== false) {
                $value .= ' ';
            }


        } catch (Exception $e) {
            // No cal fer res, és l'alineació per defecte
        }

        // Afegim el caption
        try {
            $alt= $this->extractVarName($this->currentToken['raw'], 'alt');
            $value .= '|' .$alt;

        } catch (Exception $e) {
            // totes les imatges han de contenir alt, però si no es trobes no es greu
        }



        return '{{' . $value . '}}'; // TODO: Això és l'open i el close
    }

//    protected function getContent($token) {
//
////        var_dump($this->currentToken);
////        die();
//
//        $url = $this->extractUrl($token, $width, $height, $CSSClasses);
//
//        $textPattern = "/\|(.*?)[|\]]/";
//
//
//        if (preg_match($textPattern, $token['raw'], $matchText)) {
//            $text = $matchText[1];
//        } else {
//            $text = $url;
//        }
//
//        return $this->makeTag($url, $text, $width, $height, $CSSClasses);
//    }
//
//    private function extractUrl($token, &$width = 0, &$height = 0, &$CSSclasses = '') {
//        // A diferencia dels enllaços la URL si conté un punt, el que separa la extensió.
//        // un enllaç extern només pot contenir 2 punts per separar el protocol, eliminem aquesta posibilitiat
//        $testUrl = str_replace('https:','', $token['raw']);
//        $testUrl = str_replace('http:','', $testUrl);
//        $testUrl = str_replace('ftp:','', $testUrl);
//
//
//        $urlPattern = "/{{(.*?)\|.*}}/";
//        preg_match($urlPattern, $token['raw'], $matchUrl);
//        $candidateUrl = $matchUrl[1];
//
//
//
//        $centerPattern = "/^ .*? $/";
//        $leftPattern = "/^ .*?$/";
//        $rightPattern = "/^.*? $/";
//
//
//        if (preg_match($centerPattern, $candidateUrl)) {
//            $CSSclasses = 'mediacenter';
//        } else if (preg_match($leftPattern, $candidateUrl)) {
//            $CSSclasses = 'medialeft';
//        } else if ((preg_match($rightPattern, $candidateUrl))) {
//            $CSSclasses = 'mediaright';
//        }
//
//
//
//        // estraiem la mida si escau
//        $sizePattern = "/\?(.*?)[\||}]/";
//
//        if (preg_match($sizePattern, $token['raw'], $matchSize)) {
//            $size = explode('x', $matchSize[1]);
//
//            $width = intval($size[0]);
//
//            if (count($size) == 2) {
//                $height = intval($size[1]);
//            }
//        }
//
//        // eliminem els posibles paràmetres
//        $queryPos = strpos($candidateUrl, '?');
//
//        if ($queryPos !== FALSE) {
//            $candidateUrl = substr($candidateUrl, 0, $queryPos);
//        }
//
//
//        if (strpos($testUrl, ':') === false) {
//            // és un enllaç extérn perquè no conté ':'
//            $urlPattern = "/{{(.*?) ?\|?.*?}}/";
//            preg_match($urlPattern, $token['raw'], $matchUrl);
////            var_dump($token['raw'], $urlPattern, $matchUrl);
////            die('no funciona, perquè?');
//            $url = trim($candidateUrl);
//
//        } else {
//            // és un enllaç intern
//            // cal fer servir la mateixa conversió que al htmllink? es pot fer servir el mateix mètode a tots dos llocs? <-- llavors canviar l'herencia daquesta classe a htmllink
//            $url = "https://dokuwiki.ioc.cat/lib/exe/fetch.php?media=" . trim($candidateUrl);
//
//        }
//
//
//
//        return $url;
//
//    }
//
//    private function makeTag($url, $text, $width, $height, $CSSClasses) {
//        $value = 'src="' . $url . '"';
//
//        if (strlen($text)>0) {
//            $value .= ' alt="' . $text . '"';
//        }
//
//        if ($width>0) {
//            $value .= ' width="' . $width. '"';
//        }
//
//        if ($height>0) {
//            $value .= ' height="' . $height. '"';
//        }
//
//        if (strlen($CSSClasses)>0) {
//            $value .= ' class="' . $CSSClasses. '"';
//        }
//
//        return $this->getReplacement(self::OPEN) . $value . $this->getReplacement(self::CLOSE);
//    }
}
