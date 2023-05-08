<?php
/**
 * CalculateLowerCaseInObjectArrayValues: devuelve la tabla con el contenido de la columna indicada en minúsculas
 * @culpable rafa
 */
class CalculateLowerCaseInObjectArrayValues extends CalculateFromValues {

    public function calculate($data) {
        $values = $this->getValues();
        $isArray = is_array($values[$data['table']]);
        $table = $this->castToArray($values[$data['table']]);
        foreach ($table as $k => $row) {
            $table[$k][$data['field']] = strtolower($row[$data['field']]);
        }
        return ($isArray) ? $table : json_encode($table);
    }

}
