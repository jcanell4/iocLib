<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWCode extends Html2DWInstruction {

    protected function resolveOnClose($result, $tokenEnd) {
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

            $subtype = false;
            try {
                // Comprobar si és indented
                $subtype = $this->extractVarName($this->currentToken['raw'], 'data-code-type');
            } catch (Exception $e) {
                //No fem res
            }


            switch ($subtype) {
                case 'indented':

                    $pre = '  ';
                    $post = "\n";
                    break;

                default:

                    // Es code
//                    $type = 'code';

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



        }

        preg_match($token['pattern'], $token['raw'], $match);

        $content = $match[1];


        $content = preg_replace("/<br *?\/>/ms", "\n", $content);

//        var_dump($pre . $content . $post);
//        die();
        // TODO: afegir el open de file o code segons correspongui

        return $pre . $content . $post;

    }

}
