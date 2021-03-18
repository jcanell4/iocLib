<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of QualityMethods
 *
 * @author josep
 */
class ProjectModelQualityMethods {
    public function addHistoricGestioDocument(&$data, $date=false) {
        $data['cc_historic'] = $this->getCurrentDataProject(FALSE, FALSE)['cc_historic'];
        $hist['data'] = $date?$date:date("Y-m-d");
        $hist['autor'] = $this->getUserName($data['autor']);
        $hist['modificacions'] = $data['cc_raonsModificacio'];
        $data['cc_historic'][] = $hist;
    }

    private function getUserName($users) {
        global $auth;
        $retUser = "";
        $u = explode(",", $users);
        foreach ($u as $user) {
            $retUser .= $auth->getUserData($user)['name'] . ", ";
        }
        return trim($retUser, ", ");
    }


}
