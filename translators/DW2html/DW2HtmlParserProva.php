<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../../') . '/');
if (!defined('DOKU_CONF')) define('DOKU_CONF', DOKU_INC . 'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC . 'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC . 'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC . 'inc/init.php';


$dataSource = [];

$t = '**negreta** //cursiva//
----
__subratllat__
<del>taxat</del>
<code>codi</code>
======h1======
=====h2=====
====h3====
===h4===
==h5==


----
ssaa
----
lalala
----
';
//$t = '<b>negreta</b> normal';

global $conf;


//$p = new WiocclParser($t,['testitem'=>['unitat'=>1]], $dataSource);
//print_r('<pre>');
//echo 'Text original: ' . $t . "\n";
echo "Text parsejat:\n" . DW2HtmlParser::getValue($t) . "\n";
//print_r(Html2DWParser::getValue($t,['testitem'=>['unitat'=>1]], $dataSource));
//print_r('</pre>');