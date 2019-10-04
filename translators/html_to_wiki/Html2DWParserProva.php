<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../../') . '/');
if(!defined('DOKU_CONF')) define('DOKU_CONF',DOKU_INC.'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC.'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC.'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC.'inc/init.php';



$dataSource = [];

$t = '<b>Negreta</b> normal';

global $conf;


//$p = new WiocclParser($t,['testitem'=>['unitat'=>1]], $dataSource);
print_r('<pre>');
print_r(Html2DWParser::parse($t));
//print_r(Html2DWParser::getValue($t,['testitem'=>['unitat'=>1]], $dataSource));
print_r('</pre>');