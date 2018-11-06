<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ValidateWithResponse
 *
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
