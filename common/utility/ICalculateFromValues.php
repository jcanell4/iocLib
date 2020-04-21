<?php
/**
 * interface ICalculateFromValues
 */
interface ICalculateFromValues {

    const FROM_VALUES_TYPE = "from_values";

    function init($values);

    function getValues();

}
