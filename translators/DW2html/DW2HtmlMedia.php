<?php
require_once "DW2HtmlParser.php";

class DW2HtmlMedia extends DW2HtmlImage {


    protected $urlPattern = "/{{(?:vimeo|youtube|dailymotion|altamarVideos)>(.*?)\|.*}}/";

    public function open() {

        $token = $this->currentToken;

        $token = $this->currentToken;

        // Descartem el segón paràmetre, la clau no es fa servir

        $id = '';

        if (preg_match($token['pattern'], $token['raw'], $match)) {
            $id = $match[1];
        }


        $html = '<div data-dw-block="video" data-video-id="' . $id . '" data-ioc-id="ioc_video_' . $id . '" contenteditable="false">' .
            '<div class="video">' .
            '<iframe src="https://player.vimeo.com/video/' . $id . '" width="425px" height="350px"></iframe>' .
//            '<iframe src="https://player.vimeo.com/video/176576698" width="425px" height="350px"></iframe>' .
            '</div></div>';

        return $html;


//        if ($isInnerPrevious) {
//            return $this->makeTag($url, $text, $width, $height, $CSSClasses, $isInternal);
//        } else {
//            return $this->makeLateralBox($url, $text, $CSSClasses, $isInternal);
//        }

    }

//    private function extractUrl($token, &$width = 0, &$height = 0, &$CSSclasses = '', &$isInternal = false) {
//        // A diferencia dels enllaços la URL si conté un punt, el que separa la extensió.
//        // un enllaç extern només pot contenir 2 punts per separar el protocol, eliminem aquesta posibilitiat
//        $testUrl = str_replace('https:', '', $token['raw']);
//        $testUrl = str_replace('http:', '', $testUrl);
//        $testUrl = str_replace('ftp:', '', $testUrl);
//
//
//        preg_match($this->urlPattern, $token['raw'], $matchUrl);
//        $candidateUrl = $matchUrl[1];
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
//        // Si és un enllaç ha de contenir com a mínim una barra \
//        if (strpos($testUrl, '\|') !== false) {
//
//            $urlPattern = "/{{(.*?) ?\|?.*?}}/";
//            preg_match($urlPattern, $token['raw'], $matchUrl);
//            $url = trim($candidateUrl);
//
//
//        } else {
//            $url = "lib/exe/fetch.php?media=" . trim($candidateUrl);
//            $isInternal = true;
//        }
//
//        return $url;
//
//    }
//    protected function getReplacement($position) {
//
//        $class = static::$parserClass;
//        $isInnerPrevious = $class::isInner();
//
//        // Si es inner es tracta d'un img d'una figura, si no ho és es tracta d'una imatge lateral ja que no s'accepta cap altre tipus
//        if ( $position !== self::CLOSE || !$isInnerPrevious) {
//            return parent::getReplacement($position);
//        } else {
//            return '';
//        }
//    }

//    protected function makeLateralBox($url, $text, $CSSClasses, $isInternal) {
//        $width = '200';
//
//        $value = ' data-dw-type="';
//        if ($isInternal) {
//            $value .= 'internal_image"';
//        } else {
//            $value .= 'external_image"';
//        }
//
//        $text = $this->parseContent($text);
//
//
//        $html = '<div data-dw-lateral="image" class="imgb" contenteditable="false">'
//            . '<img src="' . $url . '" class="media ' . $CSSClasses . '" title="' . $text . '" alt="' . $text . '" width="'
//            . $width .'" ' . $value . ' contenteditable="false"/>'
//            . '<div class="title" contenteditable="true">' . $text . '</div>'
//            . '</div>';
//
//        return $html;
//    }
//
//    protected function makeTag($url, $text, $width, $height, $CSSClasses, $isInternal) {
//        $value = 'src="' . $url . '"';
//
//        if (strlen($text) > 0) {
//            $value .= ' alt="' . $text . '"';
//        }
//
//        if ($width > 0) {
//            $value .= ' width="' . $width . '"';
//        }
//
//        if ($height > 0) {
//            $value .= ' height="' . $height . '"';
//        }
//
//        if (strlen($CSSClasses) > 0) {
//            $value .= ' class="' . $CSSClasses . '"';
//        }
//
//        $value .= ' data-dw-type= "';
//        if ($isInternal) {
//            $value .= 'internal_image"';
//        } else {
//            $value .= 'external_image"';
//        }
//
//        $value .= ' contenteditable="false"';
//
//        return $this->getReplacement(self::OPEN) . $value .  ' />';
//    }
//
//    // Aquest element s'autotanca
//    public function isClosing($token) {
//        return false;
//    }
}