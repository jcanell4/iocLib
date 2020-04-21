<?php
/**
 * Description of ValidateWithResponse
 */
abstract class ValidateWithPermission extends AbstractValidate {

    protected $permission;

    function getValidatorTypeData(){
        return "permission";
    }

    function init($permission) {
        $this->permission = $permission;
    }
    
}