<?php
require_once "DW2HtmlParser.php";

class DW2HtmlImageGIF extends DW2HtmlImage {

    protected $urlPattern = "/{{iocgif>(.*?)(?:\|.*?)?}}/";

    public function open() {

        $token = $this->currentToken;

        $url = $this->extractUrl($token, $width, $height, $CSSClasses, $ns, $isInternal);

        $textPattern = "/\|(.*?)}}/";


        if (preg_match($textPattern, $token['raw'], $matchText)) {
            $text = $matchText[1];
        } else {
            $text = $url;
        }

        $html = '<div class="iocgif" contenteditable="false"><img src="' . $url . '" alt="' . $text . '" title="' . $text . '" data-dw-ns="' . $ns . '"></div>';

        $this->addRefId($html);

        return $html;


    }

    protected function extractUrl($token, &$width = 0, &$height = 0, &$CSSclasses = '', &$ns, &$isInternal = false) {

        preg_match($this->getUrlPattern(), $token['raw'], $matchUrl);
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

        $url = "lib/exe/fetch.php?media=" . trim($candidateUrl);
        $isInternal = true;

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