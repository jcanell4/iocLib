<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of AbstractValidate
 *
 * @author josep
 */
abstract class AbstractValidate {
    public abstract function getValidatorTypeData();
    abstract function validate($data);
}
