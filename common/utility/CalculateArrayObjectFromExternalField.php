<?php
/**
 * Description of CalculateSingleFieldValueFromDataProject
 *
 * @author josep
 */
class CalculateArrayObjectFromExternalField extends CalculateWithPersistenceAndValues{
    const PROJECT_ID_PARAM = "projectId";
    const METADATA_SUBSET_PARAM = "metaDataSubSet";
    const FIELD_PARAM = "field";
    const FILTERING_CONDITION_PARAM = "filterCondition";
    const EXTERNAL_FIELD_SUBSET_PARAM = "externalFieldSubset";
    const FIELD_SUBSET_PARAM = "fieldSubset";
    const RESULT_ROW_PARAM = "resultRow";

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
            $ret = $this->getArrayObjectFromExternalFiled($externalValues, $data);
        }else{
            $ret = $this->getDefaultValue($data);
        }
        
        return $ret;
    }
    
    private function getArrayObjectFromExternalFiled($values, $data){
        $ret = array();
        if(isset($data[self::FILTERING_CONDITION_PARAM])){
            $condition = $data[self::FILTERING_CONDITION_PARAM];
        }else{
            $condition=TRUE;
        }
        
        
        if(isset($data[self::EXTERNAL_FIELD_SUBSET_PARAM])){
            $externallSubset = $this->getParamValue($data[self::EXTERNAL_FIELD_SUBSET_PARAM]);
        }else{
            $externallSubset = FALSE;
        }
        if(isset($data[self::FIELD_SUBSET_PARAM])){
            $subset = $this->getParamValue($data[self::FIELD_SUBSET_PARAM]);
        }else{
            $subset = FALSE;
        }
        if(isset($data[self::RESULT_ROW_PARAM])){
            $resultRow = $this->getParamValue($data[self::RESULT_ROW_PARAM]);
        }else{
            $resultRow = FALSE;
        }
        $field = $this->getParamValue($data[self::FIELD_PARAM]);
        $arrayObject = $this->getValueFieldFromValues($values, $field);
        if(is_string($arrayObject)){
            $arrayObject = json_decode($arrayObject, TRUE);
        }
        $this->setVariable(self::ARRAY_OBJECT_VALUE_VAR, $arrayObject);

        foreach ($arrayObject as $rowValue) {
            $this->setVariable(self::ROW_VALUE_VAR, $rowValue);
            if($this->getParamValue($condition)){
                if($externallSubset){
                    $pos =0;
                    $newrow = array();
                    foreach ($externallSubset as $externalField) {
                        if($subset){
                            $newrow[$subset[$pos]] = $rowValue[$externalField];
                            $pos++;
                        }else{
                            $newrow[$externalField] = $rowValue[$externalField];
                        }
                    }
                    $ret[]= $newrow;
                }elseif($resultRow){
                    $newrow = array();
                    foreach ($resultRow as $k => $v) {
                        $field = $this->getParamValue($k);
                        $value = $this->getParamValue($v);
                        $newrow[$field]=$value;
                    }
                    $ret[] = $newrow;
                }else{
                    $ret[]= $rowValue;
                }
            }
        }
        
        return $ret;        
    }    
}
