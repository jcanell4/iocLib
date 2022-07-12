<?php
require_once "DW2HtmlParser.php";

class DW2HtmlImage extends DW2HtmlInstruction {

    protected $urlPattern = "/{{(.*?)\|.*}}/";

    const DEFAULT_WIDTH = '200';

    public function getUrlPattern(){
        return $this->urlPattern;
    }

    public function open() {

        $token = $this->currentToken;
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

        // Si la imatge es interna es que es troba dins d'una caixa i és una figura
        $class = static::$parserClass;
        $isInnerPrevious = $class::isInner();

        if ($isInnerPrevious) {
            return $this->makeTag($url, $text, $width, $height, $CSSClasses, $isInternal);
        } else {
            return $this->makeLateralBox($url, $text, $CSSClasses, $isInternal);
        }

    }


    protected function extractUrl($token, &$width = 0, &$height = 0, &$CSSclasses = '', &$isInternal = false) {
        // A diferencia dels enllaços la URL si conté un punt, el que separa la extensió.
        // un enllaç extern només pot contenir 2 punts per separar el protocol, eliminem aquesta posibilitiat
        $testUrl = str_replace('https:', '', $token['raw']);
        $testUrl = str_replace('http:', '', $testUrl);
        $testUrl = str_replace('ftp:', '', $testUrl);


        preg_match($this->getUrlPattern(), $token['raw'], $matchUrl);
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
        if ( $position !== self::CLOSE || !$isInnerPrevious) {
            return parent::getReplacement($position);
        } else {
            return '';
        }
    }

    private function extractWidth($value) {
        $pattern = "/\?(\d*)\|/";
        if (preg_match($pattern, $value, $matches)) {
            return $matches[1];
        } else {
            // valor per defecte
            return self::DEFAULT_WIDTH;
        }
    }

    protected function makeLateralBox($url, $text, $CSSClasses, $isInternal) {
        $width = $this->extractWidth($this->currentToken['raw']);

        $value = ' data-dw-type="';
        if ($isInternal) {
            $value .= 'internal_image"';
        } else {
            $value .= 'external_image"';
        }

        $text = $this->parseContent($text);

        // això només es mostra al títol o com a text alternatiu
        $sanitizedText = htmlspecialchars($text);
//        $sanitizedText = str_replace('"', "'", $text);

        $html = '<div data-dw-lateral="image" class="imgb" contenteditable="false">'
            . '<img src="' . $url . '" class="media ' . $CSSClasses . '" '
            . 'title="' . $sanitizedText . '" alt="' . $sanitizedText . '" width="'
            . $width .'" ' . $value . ' contenteditable="false"/>'
            . '<div class="title" contenteditable="true">' . $text . '</div>'
            . '</div>';

        return $html;
    }

    protected function makeTag($url, $text, $width, $height, $CSSClasses, $isInternal) {
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

        $value .= ' data-dw-type= "';
        if ($isInternal) {
            $value .= 'internal_image"';
        } else {
            $value .= 'external_image"';
        }

        $value .= ' contenteditable="false"';

        return $this->getReplacement(self::OPEN) . $value .  ' />';
    }

    // Aquest element s'autotanca
    public function isClosing($token) {
        return false;
    }
}