<?php
/**
 * interface ICalculateWithPersistence
 */
interface ICalculateWithPersistence extends ICalculate{

    const WITH_PERSISTENCE_TYPE = "with_persistence";

    function getPersistence();

}
