<?php
/**
 * CalculateRealNameFromUsername: devuelve el real_name del usuario wiki
 * @culpable rafa
 */
class CalculateRealNameFromUsername extends CalculateFromValues {

    public function calculate($data) {
        $name = $this->getParamValue($data);
        $user = $this->getValues()[$name];
        $nom_real = PagePermissionManager::getUserList($user)['values'][0]['name'];
        return $nom_real;
    }
}
