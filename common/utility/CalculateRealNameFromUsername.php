<?php
/**
 * CalculateRealNameFromUsername: devuelve el real_name del usuario wiki
 * @culpable rafa
 */
class CalculateRealNameFromUsername extends CalculateWithValue {

    public function calculate($data) {
        $nom_real = PagePermissionManager::getUserList($data)['values'][0]['name'];
        return $nom_real;
    }

}
