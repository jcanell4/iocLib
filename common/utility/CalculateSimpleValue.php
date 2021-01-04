<?php
/**
 * Description of CalculateSimpleValueFromExternaField. Aquest classe permet ontenir 
 * el valor d'un camp d'un projecte extern. EL parametre data tindrà la següent estructura bàsica:
 *   - projectId: És l'identoificador del projecte extern d'on obtenir les dades
 *   - metadataSubses: És el subset que conté ñes dades del projecte a cosultar. Aquest 
 *            paràmetre és opcional i per defecte pren el vañor "main".
 *   - field: és el camp del que obtenir el valor
 * 
 * @author josep
 */
class CalculateSimpleValue extends CalculateFromValues{
    const FIELD_PARAM = "field";
    
    public function calculate($data) {
        $values = $this->getValues();
        if($values){
            $field = $this->getParamValue($data[self::FIELD_PARAM]);
            $ret = $this->getValueFieldFromValues($values, $field);
        }else{
            $ret = $this->getDefaultValue($data);
        }

        return $ret;
    }
}
