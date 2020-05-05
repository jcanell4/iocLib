<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWNote extends Html2DWInstruction {

    const NOTE = 1;
    const SIGNATURE = 2;

    protected function resolveOnClose($field) {
        die('Code#resolveOnClose');
    }

    protected function getContent($token) {


        // Format final de la nota
        //<note>
        // Aquest és un exemple de nota contextual com les que fem servir per a la comunicació i resposta d'incidències.  --- //[[rogersegu@gmail.com|Roger Segú Cabrera (IOC)]] 2013/07/30 15:04// Resposta A  --- //[[rogersegu@gmail.com|Roger Segú Cabrera (IOC)]] 2005/07/30 15:04// Resposta B  --- //[[rogersegu@gmail.com|Roger Segú Cabrera (IOC)]] 2000/07/30 15:04//
        //</note>


        $pre = $this->getReplacement(self::OPEN);
        $post = $this->getReplacement(self::CLOSE);

        // TODO: s'ha de dividir el contingut en: text - signatura

        $pattern = '/<span class="replyContent">(.*?)<\/span>\s*?<span class="ioc-signature">(.*?)<\/span>/m';

        $content = '';


        if (preg_match_all($pattern, $token['raw'], $matches)) {
            // var_dump($matches);

            for ($i = 0; $i < count($matches[0]); $i++) {
                $note = $matches[self::NOTE][$i];

                if ($i > 0) {
                    $note = '\\\\ ' . $note; // es un \\ pel salt de línia
                }

                $signature = $matches[self::SIGNATURE][$i];


                // Els <br /> no es processan correctament i els salts de línia amb \\ o doble \n son ignorats a les notes
                $note = str_replace('<br />', '', $note);

                if ($i>0) {
                    $content .= ' ';
                }

                $content .= $note . ' ' . $signature;
            }

        } else {
            // No hi han comentaris, ho descartem
            return '';
        }

        return $pre . $content . $post;
    }

}
