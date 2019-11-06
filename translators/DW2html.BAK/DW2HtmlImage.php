<?php
require_once "DW2HtmlParser.php";

class DW2HtmlImage extends DW2HtmlMarkup {


    protected function getContent($token) {

//        var_dump($this->currentToken);
//        die();

        $url = $this->extractUrl($token, $width, $height, $CSSClasses, $isInternal);

        $textPattern = "/\|(.*?)}}/";


        if (preg_match($textPattern, $token['raw'], $matchText)) {
            $text = $matchText[1];
        } else {
            $text = $url;
        }

//        var_dump($token['raw']);
//        die();


        return $this->makeTag($url, $text, $width, $height, $CSSClasses, $isInternal);
    }

    private function extractUrl($token, &$width = 0, &$height = 0, &$CSSclasses = '', &$isInternal = false) {
        // A diferencia dels enllaços la URL si conté un punt, el que separa la extensió.
        // un enllaç extern només pot contenir 2 punts per separar el protocol, eliminem aquesta posibilitiat
        $testUrl = str_replace('https:', '', $token['raw']);
        $testUrl = str_replace('http:', '', $testUrl);
        $testUrl = str_replace('ftp:', '', $testUrl);


        $urlPattern = "/{{(.*?)\|.*}}/";
        preg_match($urlPattern, $token['raw'], $matchUrl);
        $candidateUrl = $matchUrl[1];


        $centerPattern = "/^ .*? $/";
        $leftPattern = "/^ .*?$/";
        $rightPattern = "/^.*? $/";


        if (preg_match($centerPattern, $candidateUrl)) {
            $CSSclasses = 'mediacenter';
        } else if (preg_match($leftPattern, $candidateUrl)) {
            $CSSclasses = 'medialeft';
        } else if ((preg_match($rightPattern, $candidateUrl))) {
            $CSSclasses = 'mediaright';
        }


        // estraiem la mida si escau
        $sizePattern = "/\?(.*?)[\||}]/";

        if (preg_match($sizePattern, $token['raw'], $matchSize)) {
            $size = explode('x', $matchSize[1]);

            $width = intval($size[0]);

            if (count($size) == 2) {
                $height = intval($size[1]);
            }
        }

        // eliminem els posibles paràmetres
        $queryPos = strpos($candidateUrl, '?');

        if ($queryPos !== FALSE) {
            $candidateUrl = substr($candidateUrl, 0, $queryPos);
        }


        if (strpos($testUrl, ':') === false) {
            // és un enllaç extérn perquè no conté ':'
            $urlPattern = "/{{(.*?) ?\|?.*?}}/";
            preg_match($urlPattern, $token['raw'], $matchUrl);
//            var_dump($token['raw'], $urlPattern, $matchUrl);
//            die('no funciona, perquè?');
            $url = trim($candidateUrl);


        } else {
            // és un enllaç intern
            $url = "https://dokuwiki.ioc.cat/lib/exe/fetch.php?media=" . trim($candidateUrl);
            $isInternal = true;
        }

        return $url;

    }

    private function makeTag($url, $text, $width, $height, $CSSClasses, $isInternal) {
        $value = 'src="' . $url . '"';

        if (strlen($text) > 0) {
            $value .= ' alt="' . $text . '"';
        }

        if ($width > 0) {
            $value .= ' width="' . $width . '"';
        }

        if ($height > 0) {
            $value .= ' height="' . $height . '"';
        }

        if (strlen($CSSClasses) > 0) {
            $value .= ' class="' . $CSSClasses . '"';
        }

        $value .= ' data-dw-type="';
        if ($isInternal) {
            $value .= 'internal_image"';
        } else {
            $value .= 'external_image"';
        }

        return $this->getReplacement(self::OPEN) . $value . $this->getReplacement(self::CLOSE);
    }
}