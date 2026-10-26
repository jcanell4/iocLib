<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of CalculateSearchInArrayObjectAndGetFieldValue. Aquesta classe permet cercar dins d'un arrayObject
 * del propi projecte un objecte que tingui un camp amb el un valor de cerca i si el troba retorna el valor del cap de retorn.
 * Si no s'expressa camp de retorn, es retorna un valor booleà: CERT/FALS.
 * Els paramentres que arriben amb $data són:
 *  - field: Camp que conté l'ObjectArray on fer la cerca
 *  - searchField: Camp sobre el que es gestiona la cerca
 *  - searchValue: valor de cerca. És a dir, el valor que es compararà amb el camp de cerca fins trobar una coincidència
 *  - returnValues: Camp a retornar quan es trobi una coincidència entre el camp de cerca i el valor a cercar.Aquest paràmetre és opcional 
 *  - defaultValue: És el valor a retornar si la xerca és infructuosa. Aquest és un camp
 *          opcional. En cas que no es passi, es retornarà FALSE si la cerca és infroctuosa
 *
 * @author josep
 */
class CalculateExistValueInArrayObject extends CalculateFromValues{
    const FIELD_PARAM = "field";
    const SEARCH_VALUE_PARAM = "searchValue";
    const SEARCH_FIELD_PARAM = "searchField";
    const RETURN_VALUES_PARAM = "returnValues";
    
    //put your code here
    public function calculate($data) {
        $ret=FALSE;
        
        $values = $this->getValues();
        $field = $this->getParamValue($data[self::FIELD_PARAM]);
        $valueToSearch = $this->getParamValue($data[self::SEARCH_VALUE_PARAM]);
        $fieldToSearch = $this->getParamValue($data[self::SEARCH_FIELD_PARAM]);
         
        if(isset($data[self::RETURN_VALUES_PARAM])){
            $toReturn = $this->getParamValue($data[self::RETURN_VALUES_PARAM]);
        }else{
            $toReturn = FALSE;
        }
        $ret = $this->existValueInArrayObject($values, $field, $fieldToSearch, $valueToSearch, $toReturn);
        return $ret;
    }
}
