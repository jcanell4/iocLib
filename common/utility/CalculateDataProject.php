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

        if (!$ret) {
            $components = explode("#", $data["field"]);
            $field = array_shift($components);
            $ret = $values[$field];
            if ($ret) {
                foreach ($components as $field) {
                    $ret = $ret[$field];
                }
            }
        }

        if (!$ret)
            throw new Exception("Error: No s'ha trobat {$data["field"]} definit a configMain");

        return $ret;
    }
}
