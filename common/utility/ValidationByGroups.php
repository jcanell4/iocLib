<?php

if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class ValidationByGroups extends ValidateWithPermission
{

    /*
     * format de $data és: {groups:[conjunt de grups acceptats], invertedResponse:BOOLEAN}
     */
    function validate($data)
    {
        $ret = FALSE;
        $groups = $this->permission->getUserGroups();

        foreach ( $data["groups"] as $group) {
            $ret = $ret || in_array($group, $groups);
        }
        
        if($data["deniedResponse"]){
            $ret = !$ret;
        }
        return $ret;
    }
}