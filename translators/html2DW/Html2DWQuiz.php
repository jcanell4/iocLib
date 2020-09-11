<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWQuiz extends Html2DWInstruction {

    protected function resolveOnClose($field) {
        die('Code#resolveOnClose');
    }

    protected function getContent($token) {

        $patternEnunciats = '/<div class="enunciat.*?>(.*?)<\/div>/ms';

        preg_match_all($patternEnunciats, $token['raw'], $matches);

        $enunciats = count($matches) == 2 ? $matches[1] : [];

        $patternType = '/data-quiz-type="(.*?)"/ms';
        preg_match($patternType, $token['raw'], $match);
        $type = $match[1];

        // La obtenció de les solucions depén del tipus
//        $solutions = [];


        // opcional
        $extraSolutionsPattern = '/data-ioc-extra-solutions=".*?>(.*?)<\/pre>/ms';

        $extraSolutions = [];
        if (preg_match($extraSolutionsPattern, $token['raw'], $match)) {
            $extraSolutions = explode("\n", $match[1]);
            for ($i = count($extraSolutions) - 1; $i >= 0; $i--) {
                if (strlen($extraSolutions[$i]) === 0) {
                    unset($extraSolutions[$i]);
                }
            }
        }


        $content = '<quiz ' . $type . '>';

        for ($i = 0; $i < count($enunciats); $i++) {
            $content .= $enunciats[$i] . "\n";
        }

        $content .= "\n";

        switch ($type) {

            case 'complete':

                $rowPattern = '/<tr class="editable.*?>(.*?)<\/tr>/ms';

                if (preg_match_all($rowPattern, $token['raw'], $matches)) {

                    $rows = $matches[1];

                    for ($i = 0; $i < count($rows); $i++) {
                        $colPattern = '/<td.*?>(.*?)<\/td>/ms';
                        preg_match_all($colPattern, $rows[$i], $matches);
                        $cols = $matches[1];
                        $content .= '  * ' . $cols[0] . '<sol>' . $cols[1] . '</sol>' . $cols[2] . "\n";
                    }
                }

                if (count($extraSolutions) > 0) {
                    $content .= '  *';

                    for ($i = 0; $i < count($extraSolutions); $i++) {
                        $content .= ' - <sol>' . $extraSolutions[$i] . '</sol>';
                    }
                }


                break;


        }


        $content .= "</quiz>\n";


        return $content;

    }

}
