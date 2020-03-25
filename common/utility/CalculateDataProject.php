<?php

/**
 * Description of CalculateDataProject
 *
 * @author josep
 */
class CalculateDataProject extends CalculateWithPersistence{
    
    public function calculate($data) {
        $values = $this->getPersistence()->createProjectMetaDataQuery($data["projectId"], $data["subSet"])->getDataProject();
        $ret = $values[$data["field"]];
        return $ret;
    }
}
