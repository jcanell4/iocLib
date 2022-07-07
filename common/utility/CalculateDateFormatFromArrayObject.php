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
class CalculateDateFormatFromArrayObject extends CalculateFromValues
{
    const FIELD_PARAM = "field";
    const DEFAULT_SEPARATOR = "-";


    public function calculate($data)
    {
        $ret = FALSE;

        $values = $this->getValues();
        $field = $this->getParamValue($data[self::FIELD_PARAM]);
        $array = $this->setVariable(self::ARRAY_VALUE_VAR, $this->castToArray($this->getValueFieldFromValues($values, $field, $this->getDefaultValueFromData($data))));

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

    private function getDefaultValueFromData($data) {
        if (isset($data['defaultValue'])) {
            return $data['defaultValue'];
        } else {
            return [];
        }
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
        $ret = ['00', '00' ,'0000'];

        if (count($chunks) != 3) {
            // TODO: Determinar si enviem un valor per defecte o llencem una excepció
            // No cal fer res, retornarem el valor per defecte
        } else if (strlen($chunks[0]) === 4) {
            // l'any es troba al principi, intercanviem les posicions
            $this->swapDayAndMonth($chunks);
            $ret = $chunks;

        } else if (strlen($chunks[2]) === 4) {
            // No cal fer res, el format ja és correcte
            $ret = $chunks;
        }

        return implode($ret, $separator);
    }

    private function swapDayAndMonth(&$chunks) {
        $aux = $chunks[0];
        $chunks[0] = $chunks[2];
        $chunks[2] = $aux;
    }

    private function fixValueToDateYYYYMMDD($value, $separator)
    {
        // Reemplacem per assegurar que en tots els casos fem servir - (pel cas on el $valor ja és correcte
        // i no cal canviar-lo)
        $chunks = preg_split('/[-\/]/', $value);

        $ret = ['0000', '00' ,'00'];

        if (count($chunks) != 3) {
            // TODO: Determinar si enviem un valor per defecte o llencem una excepció
            // No cal fer res, retornarem el valor per defecte
        } else if (strlen($chunks[2]) === 4) {
            // l'any es troba al principi, intercanviem les posicions
            $this->swapDayAndMonth($chunks);
            $ret = $chunks;

        } else if (strlen($chunks[0]) === 4) {
            // No cal fer res, el format ja és correcte
            $ret = $chunks;
        }

        return implode($ret, $separator);

    }

}
