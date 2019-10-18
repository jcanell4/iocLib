<?php
require_once "DW2HtmlParser.php";

class DW2HtmlImage extends DW2HtmlMarkup {


    protected function getContent($token) {

//        var_dump($this->currentToken);
//        die();

        $url = $this->extractUrl($token, $width, $height);

        $textPattern = "/\|(.*?)[|\]]/";


        if (preg_match($textPattern, $token['raw'], $matchText)) {
            $text = $matchText[1];
        } else {
            $text = $url;
        }

        return $this->makeTag($url, $text, $width, $height);
    }

    private function extractUrl($token, &$width = 0, &$height = 0) {
        // A diferencia dels enllaços la URL si conté un punt, el que separa la extensió.
        // un enllaç extern només pot contenir 2 punts per separar el protocol, eliminem aquesta posibilitiat
        $testUrl = str_replace('https:','', $token['raw']);
        $testUrl = str_replace('http:','', $testUrl);
        $testUrl = str_replace('ftp:','', $testUrl);


        if (strpos($testUrl, ':') === false) {
            // és un enllaç extérn perquè no conté ':'
            $urlPattern = "/{{(.*)\|?.*?}}/";
            preg_match($urlPattern, $token['raw'], $matchUrl);
//            var_dump($token['raw'], $urlPattern, $matchUrl);
//            die('no funciona, perquè?');
            $url = $matchUrl[1];

        } else {
            // és un enllaç intern
            // cal fer servir la mateixa conversió que al htmllink? es pot fer servir el mateix mètode a tots dos llocs? <-- llavors canviar l'herencia daquesta classe a htmllink
            $url = "TODO: enllaç intern";
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
        $queryPos = strpos($url, '?');

        if ($queryPos !== FALSE) {
            $url = substr($url, 0, $queryPos);
        }


        return $url;

    }

    private function makeTag($url, $text, $width, $height) {
        $value = 'src="' . $url . '"';

        if (strlen($text)>0) {
            $value .= ' alt="' . $text . '"';
        }

        if ($width>0) {
            $value .= ' width="' . $width. '"';
        }

        if ($height>0) {
            $value .= ' height="' . $height. '"';
        }

        return $this->getReplacement(self::OPEN) . $value . $this->getReplacement(self::CLOSE);
    }
}