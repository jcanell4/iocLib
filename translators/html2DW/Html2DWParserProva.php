<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../../') . '/');
if (!defined('DOKU_CONF')) define('DOKU_CONF', DOKU_INC . 'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC . 'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC . 'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC . 'inc/init.php';


$dataSource = [];

$t = '<p><b>negreta</b> <i>cursiva</i></p>
<hr />
<p><u>subratllat</u></p>
<p><del>taxat</del></p>
<p><code>codi</code></p>
<h1>h1</h1>
<h2>h2</h2>
<h3>h3</h3>
<h4 id="h4">h4</h4>
<h5>h5</h5>
<br />
<br />
<hr />
<p>Un paràgraf.</p>
<hr>
<p>Un altre paràgraf.</p>
<hr>
<ul>
    <li>Item 1 <b>negreta</b> més text</li>
    <li>Item 2</li>
    <ul>
        <li>Subitem 2.1</li>
        <li>Subitem 2.2</li>
    </ul>
        <li>Item 3</li>
    <ul>
        <li>Subitem 3.1</li>
        <li>Subitem 3.2</li>
        <ol>
            <li>Subitem 3.2.1</li>
            <li>Subitem 3.2.2</li>
            <ul><li>Subitem 3.2.2.1</li>
                <li>Subitem 3.2.2.2 <a href="https://google.com#ancla">enllaç extern</a> <a href="/dokuwiki_30/doku.php?id=pt-loe:loe1:continguts" class="wikilink1" title="pt-loe:loe1:continguts">Presentació</a> <b>negreta</b></li></ul>
        </ol>
    </ul>
</ul>
<p>Últim paràgraf.</p>

';
//$t = '<b>negreta</b> normal';

global $conf;


//$p = new WiocclParser($t,['testitem'=>['unitat'=>1]], $dataSource);
//print_r('<pre>');
//echo 'Text original: ' . $t . "\n";
echo "Text parsejat:\n" . Html2DWParser::getValue($t) . "\n";
//print_r(Html2DWParser::getValue($t,['testitem'=>['unitat'=>1]], $dataSource));
//print_r('</pre>');