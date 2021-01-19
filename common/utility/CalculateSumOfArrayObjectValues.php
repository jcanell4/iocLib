<?php
/**
 * Description of CalculateSumOfArrayObjectValues. Aquesta classe retorna la suma
 * d'un subcamp d'un camp de tipus ArrayObject del propi projecte (values)
 * EL paràmetre d'entada ha de tenir la següent estructura:
 *  - arrayObjectfield: és el camp de l'objecte que conté l'arrayObject on practicar la suma
 *  - fieldToAdd: ës el camp que cal sumar
 *
 * @author josep
 */
class CalculateSumOfArrayObjectValues extends CalculateFromValues {
    const ARRAY_OBJECT_FIELD_PARAM = "arrayObjectfield";
    const FIELD_TO_ADD_PARAM = "fieldToAdd";

    public function calculate($data) {
        $arrayObjectfield = $this->getParamValue($data[self::ARRAY_OBJECT_FIELD_PARAM]);
        $values = $this->setVariable(self::ARRAY_OBJECT_VALUE_VAR, $this->castToArray($this->getValues()[$arrayObjectfield], FALSE));
        $sum = 0;
        if (is_array($values)) {
            foreach ($values as $item) {
                $this->setVariable(self::ROW_VALUE_VAR, $item);
                $fieldToAdd = $this->getParamValue($data[self::FIELD_TO_ADD_PARAM]);
                $sum += $item[$fieldToAdd];
            }
        }
        return $sum;
    }

}
