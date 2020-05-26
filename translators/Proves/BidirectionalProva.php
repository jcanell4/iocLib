<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../../') . '/');
if (!defined('DOKU_CONF')) define('DOKU_CONF', DOKU_INC . 'conf/');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC . 'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC . 'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC . 'inc/init.php';

require_once '../DW2html/DW2HtmlParser.php';
require_once '../html2DW/Html2DWParser.php';

$dataSource = [];

$input = file_get_contents('original.dw');
//$t = "<b>negreta</b> normal";

global $conf;


// Malauradament això no és pot fer servir, cal que la llista d'origens sigui una variable estàtica perquè s'ha de fer servir com a clau i PHP no ho admet.

//echo implode("|", array_keys(SharedConstants::ONLINE_VIDEO_CONFIG['origins']));

//
//echo implode("|", array_keys($ONLINE_VIDEO_CONFIG['origins']));
//
//exit;



$outputHtml = DW2HtmlParser::getValue($input);

// TODO ERROR! Es tanca dues vegades l'enllaç </a></a>
$outputDW = Html2DWParser::getValue($outputHtml);

//echo 'Text original: ' . $t . "\n";
//echo $outputHtml;
?>
<style>
    table {
        table-layout:fixed;
        width:800px
    }

    pre {
        overflow: hidden;
        border:1px black solid;
        padding: 15px;

        font-size: 10px;
        white-space: pre-wrap;
    }

    td {
        vertical-align: top;
    }
</style>

<table>
    <tr>
        <th style="width:33%">Original</th>
        <th style="width:33%">Html2DW -> DW2Html</th>
        <th style="width:33%">DW2Html -> Html2DW</th>
    </tr>
    <tr>
        <td><pre><?php echo htmlentities($input)?></pre></td>
        <td><pre><?php echo htmlentities($outputHtml)?></pre></td>
        <td><pre><?php echo htmlentities($outputDW)?></pre></td>
    </tr>
</table>


