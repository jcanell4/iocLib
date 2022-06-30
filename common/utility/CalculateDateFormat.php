<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CalculateSearchInArrayObjectAndGetFieldValue. Aquesta classe permet cercar dins d'un arrayObject
 * del propi projecte un objecte que tingui un camp amb el un valor de cerca i si el troba retorna el valor del cap de retorn.
 * Els paramentres que arriben amb $data són:
 *  - fieldToSearch: Camp sobre el que es gestiona la cerca
 *  - searchValue: valor de cerca. És a dir, el valor que es compararà amb el camp de cerca fins trobar una coincidència
 *  - fieldToReturn: Camp a retornar quan es trobi una coincidència entre el camp de cerca i el valor a cercar.
 *  - defaultValue: És el valor a retornar si la xerca és infructuosa. Aquest és un camp
 *          opcional. En cas que no es passi, es retornarà FALSE si la cerca és infroctuosa
 *
 * @author josep
 */
class CalculateDateFormat extends CalculateFromValues
{
    const FIELD_PARAM = "field";
    const DEFAULT_SEPARATOR = "-";


    public function calculate($data)
    {
        $ret = FALSE;

        $values = $this->getValues();
        $field = $this->getParamValue($data[self::FIELD_PARAM]);
        $array = $this->setVariable(self::ARRAY_VALUE_VAR, $this->castToArray($this->getValueFieldFromValues($values, $field)));


        $formatField = $data['formatField'];
        $format = $data['format'];
        $conditionColumn = $data['conditionColumn'];
        // el valor de la columna de condició ha de ser igual al valor
        $conditionValue = $data['conditionValue'];
        $separator = isset($data['separator']) ? $data['separator'] : self::DEFAULT_SEPARATOR;

        // Si no s'estableix la condició s'aplica a totes les files
        $always = !($conditionColumn && $conditionValue);

        // TODO: Revissar, estem aplicant el càlcul a una taula, això no serà correcte en el cas
        // d'un camp individual
        for ($i = 0; $i < count($array); $i++) {
            if ($always | $array[$i][$conditionColumn] == $conditionValue) {
                $array[$i][$formatField] = $this->applyFormat($array[$i][$formatField], $format, $separator);
            }
        }

        return $array;
    }

    private function applyFormat($value, $format, $separator)
    {
        switch ($format) {
            case 'DDMMYYYY':
                $formatted = $this->fixValueToDateDDMMYYYY($value, $separator);
                break;

            case 'YYYYMMDD':
                $formatted = $this->fixValueToDateYYYYMMDD($value, $separator);
                break;
        }
        return $formatted;
    }

    private function fixValueToDateDDMMYYYY($value, $separator)
    {
        // Reemplacem per assegurar que en tots els casos fem servir - (pel cas on el $valor ja és correcte
        // i no cal canviar-lo)
        $chunks = preg_split('/[-\/]/', $value);

        // TODO: Determinar si enviem un valor per defecte o llencem una excepció
        if (count($chunks) != 3) {
            // TODO: Determinar si enviem un valor per defecte o llencem una excepció
            // No cal fer res, retornarem el valor per defecte

        } else if (strlen($chunks[0]) === 4) {
            // l'any es troba al principi, intercanviem les posicions
            $year = $chunks[0];
            $chunks[0] = $chunks[2];
            $chunks[2] = $year;

            $value = implode($chunks, $separator);
            return $value;

        } else if (strlen($chunks[2]) === 4) {
            // No cal fer res, el format ja és correcte
            return implode($chunks, $separator);
        }

        // El format no és correcte
        return "00".$separator."00".$separator."0000";
    }

    private function fixValueToDateYYYYMMDD($value, $separator)
    {
        // Reemplacem per assegurar que en tots els casos fem servir - (pel cas on el $valor ja és correcte
        // i no cal canviar-lo)
        $chunks = preg_split('/[-\/]/', $value);



        if (count($chunks) != 3) {
            // TODO: Determinar si enviem un valor per defecte o llencem una excepció
            // No cal fer res, retornarem el valor per defecte
        } else if (strlen($chunks[2]) === 4) {
            // l'any es troba al principi, intercanviem les posicions
            $day = $chunks[0];
            $chunks[0] = $chunks[2];
            $chunks[2] = $day;

            $value = implode($chunks, $separator);
            return $value;

        } else if (strlen($chunks[2]) === 4) {
            // No cal fer res, el format ja és correcte
            return implode($chunks, $separator);
        }


        return "0000".$separator."00".$separator."00";

    }

}
