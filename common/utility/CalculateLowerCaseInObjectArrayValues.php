<?php
/**
 * CalculateLowerCaseInObjectArrayValues: devuelve la tabla con el contenido de la columna indicada en minúsculas
 * @culpable rafa
 */
class CalculateLowerCaseInObjectArrayValues extends CalculateFromValues {
    const ARRAY_OBJECT_FIELD_PARAM = "arrayObjectfield";
    const FIELD_TO_ADD_PARAM = "fieldToAdd";

    public function calculate($data) {
        $table = $this->getVariable($data['table']);
        foreach ($table as $row) {
            $row[$data['field']] = strtolower($row[$data['field']]);
        }
        return $table;
    }

}
