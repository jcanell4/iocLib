<?php

class CalculateExistValueInArrayFromExternalField extends CalculateWithPersistenceAndValues{
    const FIELD_PARAM = "field";
    const SEARCH_VALUE_PARAM = "searchValue";
    const RETURN_VALUES_PARAM = "returnValues";
    const PROJECT_ID_PARAM = "projectId";
    const METADATA_SUBSET_PARAM = "metaDataSubSet";
    
    
    //put your code here
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
            $valueToSearch = $this->getParamValue($data[self::SEARCH_VALUE_PARAM]);
            if(isset($data[self::RETURN_VALUES_PARAM])){
                $toReturn = $this->getParamValue($value);
            }else{
                $toReturn = FALSE;
            }
            
            $ret = $this->existValueInArray($externalValues, $field, $valueToSearch, $toReturn);
        }else{
            $ret = $this->getDefaultValue($data);
        }

        return $ret;
    }        
}
