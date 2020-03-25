<?php
/**
 * CalculateRealNameFromUsername: devuelve el real_name del usuario wiki
 * @culpable rafa
 */
class CalculateRealNameFromUsername extends CalculateFromValues {

    public function calculate($data) {
        $user = $this->getValues()[$data];
        $nom_real = PagePermissionManager::getUserList($user)['values'][0]['name'];
        return $nom_real;
    }

}
