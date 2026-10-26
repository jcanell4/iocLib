<?php
/**
 * Description of ValidateWithResponse
 * @author professor
 */
abstract class ValidateWithResponse extends AbstractValidate{

    protected $response;

    function getValidatorTypeData(){
        return "response";
    }

    function init($permission) {
        $this->response = $permission;
    }
}
