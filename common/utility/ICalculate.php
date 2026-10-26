<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 *
 * @author josep
 */
interface ICalculate {
    const PERSISTENCE_VAR = "persistence";
    const PROJECT_ID_VAR = "projectId";
    const VALUES_VAR = "values";
    const EXTERNAL_VALUES_VAR = "externalValues";
    const ARRAY_OBJECT_VALUE_VAR = "arrayObjectValue";
    const ARRAY_VALUE_VAR = "arrayValue";
    const ROW_VALUE_VAR = "rowValue";
    const ARRAY_ELEMENT_VAR = "arrayElement";
    function init($values, $calculatorType);
    function getDefaultValue($data);
}
