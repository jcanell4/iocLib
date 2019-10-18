<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../../') . '/');
if (!defined('DOKU_CONF')) define('DOKU_CONF', DOKU_INC . 'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC . 'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC . 'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC . 'inc/init.php';

require_once 'DW2HtmlParser.php';

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
Un paràgraf.
----
Un altre paràgraf.
----
  * Item 1 **negreta**
  * Item 2
    * Subitem 2.1
    * Subitem 2.2
  * Item 3
    * Subitem 3.1
    * Subitem 3.2
      - Subitem 3.2.1
      - Subitem 3.2.2
        * Subitem 3.2.2.1
        * Subitem 3.2.2.2 [[https://google.com#ancla|enllaç extern]] [[pt-loe:loe1:continguts|enllaç intern]] **negreta**
Últim paràgraf.
{{https://secure.php.net/images/php.gif?200x50|títol}}
{{ wiki:dokuwiki-128.png}}
{{ wiki:dokuwiki-128.png }}
{{ wiki:dokuwiki-128.png }}
';
//$t = '<b>negreta</b> normal';

global $conf;


//echo 'Text original: ' . $t . "\n";
echo "Text parsejat:\n" . DW2HtmlParser::getValue($t) . "\n";
