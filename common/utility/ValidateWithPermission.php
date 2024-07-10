<?php
/**
 * Description of ValidateWithPermission
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
