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
interface ICalculateWithConfigMain {

    const WITH_CONFIG_MAIN_TYPE = "config_main_type";

    function getConfigMainValue($key, $subsetName, $path);
}
