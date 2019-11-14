<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWCode extends Html2DWInstruction {

    protected function resolveOnClose($field) {
        die('Code#resolveOnClose');
    }

    protected function getContent($token) {

//        var_dump($token);
//        die('Code#getContent');

        $type = '';
        $lang = '';

        try {
            // No es fa sevir, només es comprova si existeix
            $aux = $this->extractVarName($this->currentToken['raw'], 'data-dw-file');

            // Es file
            $type = 'file';
            $pre = '<file>';
            $post = "\n</file>";


        } catch (Exception $e) {
            // Es code
            $type = 'code';

            $pre = '<code';
            try {
                $lang = $this->extractVarName($this->currentToken['raw'], 'data-dw-lang');
                $pre .= ' ' . $lang;
            } catch (Exception $e) {
                // No fem res

            }

            $pre .= '>';
            $post = "\n</code>";

        }

        preg_match($token['pattern'], $token['raw'], $match);

        $content = $match[1];

//        var_dump($pre . $content . $post);
//        die();
        // TODO: afegir el open de file o code segons correspongui

        return $pre . $content . $post;

    }
//    protected function getContent($token) {
//
//        // 1 . obtenir la url
//        //  1.1 és intern o extern?
//
//        $value = 0;
//
//
//        try {
//            $linkType = $this->extractVarName($this->currentToken['raw'], 'data-dw-type');
//
//            switch ($linkType) {
//                case 'internal_image':
//                    $value = $this->extractVarName($this->currentToken['raw'], 'data-dw-ns');
//                    break;
//
//                case 'external_image':
//                    $value = $this->extractVarName($this->currentToken['raw'], 'src');
//                    break;
//            }
//        } catch (Exception $e) {
//            // Si no tenim la informació ho intentem deduir
//            $value = $this->extractVarName($this->currentToken['raw'], 'src');
//
//            $pos = strpos($value, 'fetch.php?media=');
//            if ($pos !== false) {
//                $queryPos = strpos($value, '=') + 1;
//                $value = substr($value, $queryPos);
//            }
//        }
//
//        // Ajustem la mida
//        $size = '';
//
//        try {
//            $width = $this->extractVarName($this->currentToken['raw'], 'width');
//
//            // només pot haver height si hi ha width (funcionament de Dokuwiki)
//
//            $size .= '?' . $width;
//
//            try {
//                $height = $this->extractVarName($this->currentToken['raw'], 'height');
//
//                $size .= 'x'.$height;
//
//            } catch (Exception $e) {
//                // No cal fer res, només s'afegeix l'amplada
//            }
//
//        } catch (Exception $e) {
//            // No cal fer res, és l'alineació per defecte
//        }
//
//        $value .= $size;
//
//
//        // Ajustem l'alineament
//        try {
//            $CSSClasses = $this->extractVarName($this->currentToken['raw'], 'class');
//
//
//            if (strpos($CSSClasses, 'mediacenter') !== false) {
//
//                $value = ' ' . $value . ' ';
//            } else if (strpos($CSSClasses, 'medialeft') !== false) {
//                $value = ' ' . $value;
//
//            } else if (strpos($CSSClasses, 'mediaright') !== false) {
//                $value .= ' ';
//            }
//
//
//        } catch (Exception $e) {
//            // No cal fer res, és l'alineació per defecte
//        }
//
//        // Afegim el caption
//        try {
//            $alt= $this->extractVarName($this->currentToken['raw'], 'alt');
//            $value .= '|' .$alt;
//
//        } catch (Exception $e) {
//            // totes les imatges han de contenir alt, però si no es trobes no es greu
//        }
//
//
//
//        return '{{' . $value . '}}'; // TODO: Això és l'open i el close
//    }

}
