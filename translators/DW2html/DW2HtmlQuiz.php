<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';


class DW2HtmlQuiz extends DW2HtmlInstruction {

    public static $counter = 0;

    public function close() {
        return '';
    }

    public function open() {

        $token = $this->currentToken;
        // El contingut dintre d'aquest block no parseja, es deixa tal qual

        $content = "";
        $value = "";

        if (preg_match($token['pattern'], $token['raw'], $match)) {
            $type = $match[1];
            $content = $match[2];
        } else {
            return "ERROR: No s'ha trobat coincidencia amb el patró";
        }

        $lines = explode("\n", $content);

        $startIndex = -1;
        for ($i = 0; $i < count($lines); $i++) {
            if (substr($lines[$i], 0, 3) === "  *") {
                $startIndex = $i;
                break;
            }
        }

        if ($i === -1) {
            return "ERROR: No s'ha trobat cap opció/resposta";
        }


        $id = "ioc-quiz-" . date_timestamp_get(date_create());

        $value .= '<div id="' . $id . '" class="ioc-quiz" data-quiz-type="'. $type .'" contenteditable="false">';
        $value .= '<div class="editable-text">';

        $value .= '<div class="enunciat editable" contenteditable="true">';
        for ($i = 0; $i < $startIndex; $i++) {
            $value .= '<div>' . $lines[$i] . '</div>';
        }
        $value .= '</div>';

        $value .= '<div>';
        $value .= '<table id="table_' . $id . '" class="opcions" contenteditable="true">';
        $value .= '<tbody>';


        // TODO: Processar la capçalera i files de forma diferent segons el $type
        $options = [];
        for ($i = $startIndex; $i < count($lines); $i++) {
            if (strlen(rtrim($lines[$i])) > 0) {
                $options[] = ($lines[$i]);
            }

        }

        $extrapattern = '/^  \* - ?<sol>/ms';
        $extraSolutions = $options[count($options)-1];
        $extraSolutionContent = false;
        if (preg_match($extrapattern, $extraSolutions, $match)) {
            $extraSolutionContent = array_pop($options);
        }



        $value .= $this->getRows($type, $options, $id);


        $value .= '</tbody>';



        $value .= '</table>';

        if ($type === 'choice') {
            $value .= '<div class="hidden-field"></div>';
        }


        // TODO: Si hi han solucions extras s'afegeixen abans de tancar
        if ($extraSolutionContent) {
            $extrapattern = '/<sol>(.*?)<\/sol>/ms';
            preg_match_all($extrapattern, $extraSolutionContent, $matches);

            $value .= '<div class="extra-solutions editable-text">';
            $value .= '<label>Introdueix solucions errónies adicionals separades per un salt de línia:</label>';

            $value .= '<textarea class="extra-solutions editable" rows="4">';

            for ($i = 0; $i <count($matches[1]); $i++) {
                $value.=rtrim($matches[1][$i]) . "\n";
            }

            $value .= '</textarea>';

            $value .= '<pre data-ioc-extra-solutions=""></pre>';

            $value .= '</div>';
        }


        $value .= '</div>';
        $value .= '</div>';
        $value .= '</div>';

        return $value;
    }


    function getRows($type, $options, $id) {
        switch ($type) {

            case 'vf':
                return $this->getRowsVF($options, $id);

            case 'choice':
                return $this->getRowsChoice($options, $id);

            case 'relations':
                return $this->getRowsRelations($options);

            case 'complete':
                return $this->getRowsComplete($options);
        }

        return '';
    }

    function getRowsVF($options, $id) {


        $value = '<tr contenteditable="false"><th>Pregunta</th><th>V</th><th>F</th><th>Accions</th></tr>';


        for ($i = 0; $i < count($options); $i++) {
            $value .= '<tr class="editable">';

            $suffix = substr($options[$i], -3);
            $content = rtrim(substr($options[$i], 4, count($options[$i]) - 4));

            $value .= '<td>' . $content . '</td>';

            if ($suffix === '(V)') {
                $value .= '<td class="center" contenteditable="false">';
                $value .= '<input type="radio" name="' . $id . '_' . self::$counter . '" value="true" checked="true">';
                $value .= '</td>';

                $value .= '<td class="center" contenteditable="false">';
                $value .= '<input type="radio" name="' . $id . '_' . self::$counter . '" value="false">';
                $value .= '</td>';
                $value .= '<td class="hidden-field">true</td>';
            } else {
                $value .= '<td class="center" contenteditable="false">';
                $value .= '<input type="radio" name="' . $id . '_' . self::$counter . '" value="true">';
                $value .= '</td>';

                $value .= '<td class="center" contenteditable="false">';
                $value .= '<input type="radio" name="' . $id . '_' . self::$counter . '" value="false" checked="true">';
                $value .= '</td>';
                $value .= '<td class="hidden-field">false</td>';
            }



            $value .= '<td contenteditable="false"><span class="iocDeleteIcon actionIcon delete" title="Eliminar"></span></td>';

            $value .= '</tr>';

            self::$counter++;

        }

        return $value;
    }

    function getRowsChoice($options, $id) {


        $value = '<tr contenteditable="false"><th>Pregunta</th><th>Resposta</th><th>Accions</th></tr>';


        for ($i = 0; $i < count($options); $i++) {
            $value .= '<tr class="editable">';

            $suffix = substr($options[$i], -4);


            if ($suffix === '(ok)') {
                $content = rtrim(substr($options[$i], 4, count($options[$i]) - 5));
                $value .= '<td>' . $content . '</td>';

                $value .= '<td class="center" contenteditable="false">';
                $value .= '<input type="radio" name="' . $id . '_' . self::$counter . '" checked="true">';
                $value .= '</td>';
            } else {
                $content = rtrim(substr($options[$i], 4));
                $value .= '<td>' . $content . '</td>';

                $value .= '<td class="center" contenteditable="false">';
                $value .= '<input type="radio" name="' . $id . '_' . self::$counter . '">';
                $value .= '</td>';
            }

            $value .= '<td contenteditable="false"><span class="iocDeleteIcon actionIcon delete" title="Eliminar"></span></td>';


            $value .= '</tr>';

        }


        self::$counter++;

        return $value;
    }


    function getRowsRelations($options) {


        $value = '<tr contenteditable="false"><th>Pregunta</th><th>Resposta</th><th>Accions</th></tr>';

        for ($i = 0; $i < count($options); $i++) {
            $value .= '<tr class="editable">';

            $pattern = '/  \*(.*?)<sol>(.*?)<\/sol>/ms';


            preg_match($pattern, $options[$i], $match);

            $value .= '<td>';
            $value .= trim($match[1]);
            $value .= '</td>';
            $value .= '<td>';
            $value .= trim($match[2]);
            $value .= '</td>';

            $value .= '<td contenteditable="false"><span class="iocDeleteIcon actionIcon delete" title="Eliminar"></span></td>';


            $value .= '</tr>';

        }

        return $value;
    }

    function getRowsComplete($options) {


        $value = '<tr contenteditable="false"><th>Text previ</th><th>Solució</th><th>Text posterior</th><th>Accions</th></tr>';

        for ($i = 0; $i < count($options); $i++) {
            $value .= '<tr class="editable">';

            $pattern = '/  \*(.*?)<sol>(.*?)<\/sol>(.*?)$/ms';


            preg_match($pattern, $options[$i], $match);

            $value .= '<td>';
            $value .= trim($match[1]);
            $value .= '</td>';
            $value .= '<td>';
            $value .= trim($match[2]);
            $value .= '</td>';
            $value .= '<td>';
            $value .= trim($match[3]);
            $value .= '</td>';

            $value .= '<td contenteditable="false"><span class="iocDeleteIcon actionIcon delete" title="Eliminar"></span></td>';


            $value .= '</tr>';

        }

        return $value;
    }
}
