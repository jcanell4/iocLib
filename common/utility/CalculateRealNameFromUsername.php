<?php
/**
 * CalculateRealNameFromUsername: devuelve el real_name del usuario wiki
 * @culpable rafa
 */
class CalculateRealNameFromUsername extends CalculateFromValues {

    public function calculate($data) {
        $name = $this->getParamValue($data);
        $user = $this->getValues()[$name];
        if (strpos($user, ",")) {
            $nom_real = PagePermissionManager::getUsers($user);
        }else {
            $nom_real = PagePermissionManager::getUserList($user)['values'][0]['name'];
        }
        return $nom_real;
    }
}
