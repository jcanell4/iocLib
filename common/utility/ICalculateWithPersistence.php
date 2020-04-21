<?php
/**
 * interface ICalculateWithPersistence
 */
interface ICalculateWithPersistence {

    const WITH_PERSISTENCE_TYPE = "with_persistence";

    function init($value);

    function getPersistence();

}
