<?php
require_once "DW2HtmlParser.php";

class DW2HtmlImageGIF extends DW2HtmlImage {


    protected $urlPattern = "/{{iocgif>(.*?)(?:\|.*?)?}}/";

    public function open() {

        $token = $this->currentToken;
//        var_dump($this->currentToken);
//        die();

        $url = $this->extractUrl($token, $width, $height, $CSSClasses, $ns, $isInternal);

        $textPattern = "/\|(.*?)}}/";


        if (preg_match($textPattern, $token['raw'], $matchText)) {
            $text = $matchText[1];
        } else {
            $text = $url;
        }

        $html = '<div class="iocgif"><img src="' . $url . '" alt="' . $text . '" title="' . $text . '" data-dw-ns="' . $ns . '"></div>';


        return $html;


    }

    protected function extractUrl($token, &$width = 0, &$height = 0, &$CSSclasses = '', &$ns, &$isInternal = false) {
        // A diferencia dels enllaços la URL si conté un punt, el que separa la extensió.
        // un enllaç extern només pot contenir 2 punts per separar el protocol, eliminem aquesta posibilitiat
        $testUrl = str_replace('https:', '', $token['raw']);
        $testUrl = str_replace('http:', '', $testUrl);
        $testUrl = str_replace('ftp:', '', $testUrl);


        preg_match($this->urlPattern, $token['raw'], $matchUrl);
        $candidateUrl = $matchUrl[1];

        if (strpos($candidateUrl, '?')) {
            $ns = substr($candidateUrl, 0, strpos($candidateUrl, '?'));
        } else {
            $ns = $candidateUrl;
        }


        // eliminem els posibles paràmetres
        $queryPos = strpos($candidateUrl, '?');

        if ($queryPos !== FALSE) {
            $candidateUrl = substr($candidateUrl, 0, $queryPos);
        }


        // Si és un enllaç ha de contenir com a mínim una barra \
        if (strpos($testUrl, '\|') !== false) {

            $urlPattern = "/{{(.*?) ?\|?.*?}}/";
            preg_match($urlPattern, $token['raw'], $matchUrl);
            $url = trim($candidateUrl);


        } else {
            $url = "lib/exe/fetch.php?media=" . trim($candidateUrl);
            $isInternal = true;
        }

        return $url;

    }

    protected function getReplacement($position) {

        $class = static::$parserClass;
        $isInnerPrevious = $class::isInner();

        // Si es inner es tracta d'un img d'una figura, si no ho és es tracta d'una imatge lateral ja que no s'accepta cap altre tipus
        if ($position !== self::CLOSE || !$isInnerPrevious) {
            return parent::getReplacement($position);
        } else {
            return '';
        }
    }

}