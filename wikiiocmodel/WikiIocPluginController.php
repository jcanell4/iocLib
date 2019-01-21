<?php
/*
 * WikiIocPluginController: Gestiona la variable global $plugin_controller
 */
if (!defined('DOKU_INC')) die();
//require_once(DOKU_INC.'inc/inc_ioc/ioc_plugincontroller.php');

class WikiIocPluginController {

    /*
     * Obtiene el directorio de proyecto correspondiente al tipo de proyecto especificado
     */
    public static function getProjectTypeDir($projectType=FALSE) {
        global $plugin_controller;
        return $plugin_controller->getProjectTypeDir($projectType);
    }
}
