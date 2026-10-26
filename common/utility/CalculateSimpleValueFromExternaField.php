<?php
/**
 * Description of CalculateSimpleValueFromExternaField. Aquest classe permet ontenir 
 * el valor d'un camp d'un projecte extern. EL parametre data tindrà la següent estructura bàsica:
 *   - projectId: És l'identoificador del projecte extern d'on obtenir les dades
 *   - metadataSubset: És el subset que conté ñes dades del projecte a cosultar. Aquest 
 *            paràmetre és opcional i per defecte pren el vañor "main".
 *   - field: és el camp del que obtenir el valor
 * 
 * @author josep
 */
class CalculateSimpleValueFromExternaField extends CalculateWithPersistenceAndValues{
    const PROJECT_ID_PARAM = "projectId";
    const METADATA_SUBSET_PARAM = "metaDataSubSet";
    const FIELD_PARAM = "field";
    
    public function calculate($data) {
        if(isset($data[self::PROJECT_ID_PARAM])){
            $projectId = $this->getParamValue($data[self::PROJECT_ID_PARAM]);
        }else{
            throw new Exception("Error de configuració: No es reconeix el projecte a tractar");
        }
        if(isset($data[self::METADATA_SUBSET_PARAM])){
            $metadataSubset = $this->getParamValue($data[self::METADATA_SUBSET_PARAM]);
        }else{
             $metadataSubset = "main";
        }
        
        $externalValues = $this->setVariable(self::EXTERNAL_VALUES_VAR, $this->getPersistence()
                        ->createProjectMetaDataQuery($projectId, $metadataSubset)
                        ->getDataProject());
        if($externalValues){
            $field = $this->getParamValue($data[self::FIELD_PARAM]);
            $ret = $this->getValueFieldFromValues($externalValues, $field,  $this->getDefaultValue($data));
        }else{
            $ret = $this->getDefaultValue($data);
        }

        return $ret;
    }
}
