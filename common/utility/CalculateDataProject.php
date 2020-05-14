<?php
/**
 * Description of CalculateDataProject
 *
 * @author josep
 */
class CalculateDataProject extends CalculateWithPersistence{

    public function calculate($data) {
        $values = $this->getPersistence()
                        ->createProjectMetaDataQuery($data[ProjectKeys::KEY_PROJECT_ID], $data[ProjectKeys::KEY_METADATA_SUBSET])
                        ->getDataProject();
        $ret = $values[$data["field"]];
        return $ret;
    }
}
