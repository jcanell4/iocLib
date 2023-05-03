<?php
/**
 * Description of CalculateConditionalValue. Aquesta classe permet obtenir 
 * un valor en funció de la certesa o falsedat d'una condició. EL parametre data tindrà la següent estructura bàsica:
 *   - condition: És un valor, una operació o un càlcul de tipus booleà
 *   - valueIfTrue: És valor a retornar si la condició avalua a cert.
 *   - valueIfFalse: És valor a retornar si la condició avalua a fals.
 * 
 * EXEMPLE 1:
 * ...
 * "calculateOnSave":{
 *      "class": "CalculateConditionalValue",
 *      "data:{
    *      "condition":{
    *          "_type_":"operation",
    *           "values":[
    *              {
    *                  "_type_":"field",
    *                  "name":"durada",
    *                  "values":"$values"
    *              },
    *              "anual"
    *          ],
    *          "operation":"=="
    *      },
    *      "valueIfTrue":140,
    *      "valueIfFalse":70,      
 *      }
 * }
 * ...
 * 
 * EXEMPLE 2:
 * ...
  "calculateOnSave":{
       "class": "CalculateConditionalValue",
       "data:{
          "condition":{
              "_type_":"field",
              "name":"isSum",
              "values":"$values"
          },
          "valueIfTrue":{
              "_type_":"calculatedFromValues",
               "class":"CalculateSumOfArrayObjectValues",
               "values":$values
               "data":{
                   "arrayObjectfield":"taulaDadesNumeriques",
                   "fieldToAdd":"quantitat"
               }
          },
          "valueIfFalse":{
              "_type_":"field",
              "name":"defaultFieldValue",
              "values":$values
          }     
       } 
    }
 * ...
 * 
 * 
 * 
 * @author josep
 */
class CalculateConditionalValue extends CalculateFromValues{
    const CONDITION_PARAM = "condition";
    const VALUE_IF_TRUE_PARAM = "valueIfTrue";
    const VALUE_IF_FALSE_PARAM = "valueIfFalse";
    
    public function calculate($data) {
        $condition = $this->getParamValue($data[self::CONDITION_PARAM]);
        $ifTrue = $this->getParamValue($data[self::VALUE_IF_TRUE_PARAM]);
        $ifFalse = $this->getParamValue($data[self::VALUE_IF_FALSE_PARAM]);
        
        $ret  = $condition?$ifTrue:$ifFalse;

        return $ret;
    }

}
