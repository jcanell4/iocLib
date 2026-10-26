<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../') . '/');
if(!defined('DOKU_CONF')) define('DOKU_CONF',DOKU_INC.'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC.'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC.'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC.'inc/init.php';

if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC."lib/lib_ioc/");
require_once DOKU_LIB_IOC . "upgrader/CommonUpgrader.php";