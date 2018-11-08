<?php

require_once(__DIR__ . "/AbstractValidate.php");

abstract class ValidateWithPermission extends AbstractValidate
{
    protected $permission;
    
    function getValidatorTypeData(){
        return "permission";
    }

    function init($permission) {
        $this->permission = $permission;
    }
}