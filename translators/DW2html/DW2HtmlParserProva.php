<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../../') . '/');
if (!defined('DOKU_CONF')) define('DOKU_CONF', DOKU_INC . 'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC . 'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC . 'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC . 'inc/init.php';

require_once 'DW2HtmlParser.php';

$dataSource = [];

$t = "
p1
p2
p3
----
**negreta** __subratllat__ //cursiva//

//cursiva// **negreta** __subratllat__

__subratllat__ **negreta** //cursiva//

test (paràgraf que no comença amb canvi d'estat) **negreta** //cursiva// __subratllat__

----

__subratllat__

<del>taxat</del>
======h1======
foo bar
=====h2=====
====h3====
===h4===
==h5==

''test 2: code inline que ocupa tota la línia'' amb text final (això funciona)

''test 2: code inline que ocupa tota la línia, això no''

----
Un paràgraf amb ''code inline això no s'ha de **processar**'' escapat?.

<code java>
block de codi
en dos línies
</code>



PARAGRAPH




<file>
block de file
en dos línies
</file>
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
{{https://secure.php.net/images/php.gif?200x50|títol 1}}{{ wiki:dokuwiki-128.png?200|títol 2}}{{ wiki:dokuwiki-128.png?200x50 |títol 3}}{{wiki:dokuwiki-128.png?200x50 |títol 4}}";
//$t = '<b>negreta</b> normal";

global $conf;


//echo 'Text original: ' . $t . "\n";
echo "Text parsejat:\n" . DW2HtmlParser::getValue($t) . "\n";
