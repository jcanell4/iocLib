<?php
/**
 * Description of AbstractValidate
 *
 * @author josep
 */
abstract class AbstractValidate {

    public abstract function getValidatorTypeData();
    abstract function validate($data);

    
}
