<?php

if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class ValidationByInfoPerm extends ValidateWithPermission
{

    /*
     * format de $data és: {roles:[conjunt de roles acceptats], invertedResponse:BOOLEAN}
     */
    function validate($data)
    {
        $perm = $this->permission->getInfoPerm();
        $ret = FALSE;

        if($perm){
            $ret = $perm >= $data["perm"];
        }
        if($data["deniedResponse"]){
            $ret = !$ret;
        }
        return $ret;
    }
}