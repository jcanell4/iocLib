<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWQuiz extends Html2DWInstruction {

    protected function resolveOnClose($field) {
        die('Code#resolveOnClose');
    }

    protected function getContent($token) {

        $raw = str_replace('<br />', "\n", $token['raw']);
        $raw = str_replace('<br>', "\n", $raw);


        // hi han 2 opcions, l'enunciat pot ser una única línia o múltiple, si és multiple cad alínia es troba en un div propi

        $patternEnunciats = '/<div class="enunciat.*?>(<div>.*?<\/div>)<\/div>/ms';
        if (preg_match_all($patternEnunciats, $raw, $matches)) {

            $patternEnunciat = '/<div>(.*?)<\/div>/ms';

            $enunciats = [];

            for ($i = 0; $i < count($matches[1]); $i++) {

                if (preg_match_all($patternEnunciat, $matches[1][$i], $matchEnunciat)) {
                    $enunciats = array_merge($enunciats, $matchEnunciat[1]);
                }

            }

        }


        $patternType = '/data-quiz-type="(.*?)"/ms';
        preg_match($patternType, $raw, $match);
        $type = $match[1];

        // La obtenció de les solucions depén del tipus
//        $solutions = [];


        // opcional
        $extraSolutionsPattern = '/data-ioc-extra-solutions=".*?>(.*?)<\/pre>/ms';

        $extraSolutions = [];
        if (preg_match($extraSolutionsPattern, $raw, $match)) {
            $extraSolutions = explode("\n", $match[1]);
            for ($i = count($extraSolutions) - 1; $i >= 0; $i--) {
                if (strlen($extraSolutions[$i]) === 0) {
                    unset($extraSolutions[$i]);
                }
            }
        }


        $content = '<quiz ' . $type . ">\n";

        for ($i = 0; $i < count($enunciats); $i++) {
            $value = rtrim($enunciats[$i]);

            if (strlen($value)>0) {
                $content .= $value . "\n";
            }
        }

        $content .= "\n";

        switch ($type) {

            case 'complete':

                $rowPattern = '/<tr class="editable.*?>(.*?)<\/tr>/ms';

                if (preg_match_all($rowPattern, $raw, $matches)) {

                    $rows = $matches[1];

                    for ($i = 0; $i < count($rows); $i++) {
                        $colPattern = '/<td.*?>(.*?)<\/td>/ms';
                        preg_match_all($colPattern, $rows[$i], $matches);
                        $cols = $matches[1];
                        $content .= '  * ' . $cols[0] . ' <sol>' . $cols[1] . '</sol> ' . $cols[2] . "\n";
                    }
                }

                if (count($extraSolutions) > 0) {
                    $content .= '  *';

                    for ($i = 0; $i < count($extraSolutions); $i++) {
                        $content .= ' - <sol>' . $extraSolutions[$i] . '</sol>';
                    }

                    $content .= "\n";
                }


                break;

            case 'vf':

                $rowPattern = '/<tr class="editable.*?>(.*?)<\/tr>/ms';

                if (preg_match_all($rowPattern, $raw, $matches)) {

                    $rows = $matches[1];

                    for ($i = 0; $i < count($rows); $i++) {
                        $solucioIdPattern = '/<td class="hidden-field".*?>(.*?)<\/td>/ms';
                        preg_match($solucioIdPattern, $rows[$i], $matches);
                        $solucio = $matches[1] === 'true' ? ' (V)' : ' (F)';


                        $colPattern = '/<td.*?>(.*?)<\/td>/ms';
                        // en aquest nomes agafem el valor de la primera columna
                        preg_match($colPattern, $rows[$i], $matches);
                        $cols = rtrim($matches[1]);

                        $content .= '  * ' . $cols . $solucio . "\n";;
                    }
                }

                break;

            case 'choice':
                $rowPattern = '/(<tr class="editable.*?>.*?)<\/tr>/ms';

                if (preg_match_all($rowPattern, $raw, $matches)) {

                    $rows = $matches[1];

                    $solucioIdPattern = '/<div class="hidden-field".*?>(.*?)<\/div>/ms';
                    preg_match($solucioIdPattern, $raw, $matches);
                    $solucioId = $matches[1];

                    for ($i = 0; $i < count($rows); $i++) {

                        $idPattern = '/<tr.*?data-row-id="' . $solucioId . '"/ms';

                        $solucio = preg_match($idPattern, $rows[$i], $matches) === 1;


                        $colPattern = '/<td.*?>(.*?)<\/td>/ms';
                        // en aquest nomes agafem el valor de la primera columna
                        preg_match($colPattern, $rows[$i], $matches);
                        $cols = rtrim($matches[1]);


                        $content .= '  * ' . $cols . ($solucio ? ' (ok)' : '') . "\n";;
                    }
                }
                break;

            case 'relations':

                $rowPattern = '/<tr class="editable.*?>(.*?)<\/tr>/ms';

                if (preg_match_all($rowPattern, $raw, $matches)) {

                    $rows = $matches[1];

                    for ($i = 0; $i < count($rows); $i++) {
                        $colPattern = '/<td.*?>(.*?)<\/td>/ms';
                        preg_match_all($colPattern, $rows[$i], $matches);
                        $cols = $matches[1];
                        $content .= '  * ' . $cols[0] . ' <sol>' . $cols[1] . "</sol>\n";
                    }
                }

                break;

        }


        $content .= "</quiz>\n";


        return $content;

    }

}
