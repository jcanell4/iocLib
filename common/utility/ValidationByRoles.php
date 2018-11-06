<?php

if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

class ValidationByRoles extends ValidateWithPermission
{

    /*
     * format de $data és: {roles:[conjunt de roles acceptats], invertedResponse:BOOLEAN}
     */
    function validate($data)
    {
        $role = $this->permission->getRol();
        $ret = FALSE;

        if(is_array($role)){
            for ($i=0; $i<count($role) && $ret; $i++){
                $ret = in_array($role[$i], $data["roles"]);                
            }
        }else if(strpos($role, ',') !== false){
            $arole = explode(',', $role);
            for ($i=0; $i<count($arole) && $ret; $i++){
                $ret = in_array($arole[$i], $data["roles"]);                
            }
        }else{
            $ret = in_array($role, $data["roles"]);
        }
        if($data["deniedResponse"]){
            $ret = !$ret;
        }
        return $ret;
    }
}