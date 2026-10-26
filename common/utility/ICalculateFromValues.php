<?php
/**
 * interface ICalculateFromValues
 */
interface ICalculateFromValues extends ICalculate{

    const FROM_VALUES_TYPE = "from_values";

    function getValues();

}
