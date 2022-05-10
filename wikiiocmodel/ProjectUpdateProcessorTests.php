<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../') . '/');
if(!defined('DOKU_CONF')) define('DOKU_CONF',DOKU_INC.'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC.'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC.'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC.'inc/init.php';



    /**
     * Compara si dos valors són iguals o no. Els valors han de ser de tipus string però 
     * si algun d'ells es troba tancat entre els caracters [] o els caracters (), es consierarà 
     * que conté multivalors separats per comes. Si el caracter de tancament és [], la comparació 
     * serà certa si hi ha conincidència amb algun del múltiples valors. Per contra si el 
     * els caràcters de tancament són (), la comparació només serà certa si l'altre valor és 
     * també multivalor i coincideixen tots els seus elements.
     * Exemples:
     * - $v1 = "Cadena única"
     * - $v2 = "Cadena única"
     *  Resultat = true
     * - $v1 = "Cadena única"
     * - $v2 = "Una altre cadena"
     *  Resultat = false
     * - $v1 = "Cadena única"
     * - $v2 = "[Cadena única, Una altre cadena]"
     *  Resultat = true perquè hi ha un valor a $v2 coincident
     * - $v1 = "Cadena única"
     * - $v2 = "(Cadena única, Una altre cadena)"
     *  Resultat = false perquè hi ha un valor a $v2 que no coeincidex amb $v1
     * - $v1 = "Cadena única"
     * - $v2 = "(Cadena única)"
     *  Resultat = true perquè tots els valors de $v2 coincidexen amb la cadena de $v1
     * - $v1 = "Cadena única"
     * - $v2 = "(Cadena única, Cadena única)"
     *  Resultat = true perquè tots els valors de $v2 coincidexen amb la cadena de $v1
     * - $v1 = "[cadena 1, cadena 2]"
     * - $v2 = "[cadena 3, cadena 4]"
     *  Resultat = false perquè cap dels valors de $v1 conicideix amb cap dels valors de $v2
     * - $v1 = "[cadena 1, cadena 2]"
     * - $v2 = "[cadena 3, cadena 4, cadena 1]"
     *  Resultat = True perquè almenys un dels valors de $v1 conicideix amb un dels valors de $v2
     * - $v1 = "(cadena 1, cadena 2)"
     * - $v2 = "[cadena 3, cadena 2, cadena 1]"
     *  Resultat = True perquè tots els valors de $v1 conicideixen amb algun dels valors de $v2
     * - $v1 = "(cadena 1, cadena 2)"
     * - $v2 = "[cadena 3, cadena 4, cadena 1]"
     *  Resultat = False perquè no tots els valors de $v1 conicideixen amb algun dels valors de $v2
     * - $v1 = "(cadena 1, cadena 2)"
     * - $v2 = "(cadena 3, cadena 2, cadena 1)"
     *  Resultat = False perquè hi ha un element de $v2 al que no li correcpon cap element de $v1
     * - $v1 = "(cadena 1, cadena 2)"
     * - $v2 = "(cadena 2, cadena 2, cadena 1)"
     *  Resultat = True perquè tots els valors de $v1 i $v2 tenen una correspondència
     * 
     * És una funció commutativa. Ésa a dir és indiferent fer ::__equalCompare__($v1, $v2) 
     * que ::__equalCompare__($v2, $v1)
     * @param type $v1
     * @param type $v2
     */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$testParams = [
    ['a, a', "Cadena única", "Cadena única", true],
    ['a, b', "Cadena única", "Una altre cadena", false],
    ['a, [a,b]', "Cadena única", "[Cadena única, Una altre cadena]", true],
    ['a, (a,b)', "Cadena única", "(Cadena única, Una altre cadena)", false],
    ['a, (a)', "Cadena única", "(Cadena única)", true],
    ['a, (a,a)', "Cadena única", "(Cadena única, Cadena única)", true],
    ['[1, 2], [3, 4]', "[cadena 1, cadena 2]", "[cadena 3, cadena 4]", false],
    ['[1, 2], [3, 4, 1]', "[cadena 1, cadena 2]", "[cadena 3, cadena 4, cadena 1]", true],
    ['(1, 2), [3, 2, 1]', "(cadena 1, cadena 2)", "[cadena 3, cadena 2, cadena 1]", true],
    ['(1, 2), [3, 4, 1]', "(cadena 1, cadena 2)", "[cadena 3, cadena 4, cadena 1]", false],
    ['(1, 2), (3, 2, 1)', "(cadena 1, cadena 2)", "(cadena 3, cadena 2, cadena 1)", false],

    // Aquestes fallen:
    ['(1, 2), (2, 2, 1)', "(cadena 1, cadena 2)", "(cadena 2, cadena 2, cadena 1)", true],
    ['(1, 2, 2), (1, 2)', "(cadena 1, cadena 2, cadena 2)", "(cadena 1, cadena 2)", true],
    ['(1, 2), (1, 2)', "(cadena 1, cadena 2)", "(cadena 1, cadena 2)", true],

];

echo '<pre>';
for($i = 0; $i< count($testParams); $i++) {

    test($i, $testParams[$i][0],  $testParams[$i][1],  $testParams[$i][2],  $testParams[$i][3]);
}
echo '</pre>';


function test($i, $case, $param1, $param2, $expected) {
    $resultat = ArrayFieldProjectUpdateProcessor::__equalCompare__($param1, $param2);
    echo "Test {$i}: {$case}. Params: {$param1}, {$param2}. Esperat: {$expected}, Retornat: {$resultat}. <b ";
    if ($resultat == $expected) {
        echo "style='color: green'>SUCCESS!";
    } else {
        echo "style='color: red'>FAIL!";
    }
    echo "</b><br/>";
}