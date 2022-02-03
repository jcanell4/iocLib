<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../../') . '/');
if (!defined('DOKU_CONF')) define('DOKU_CONF', DOKU_INC . 'conf/');

require_once DOKU_INC . 'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC . 'inc/inc_ioc/ioc_project_load.php';
require_once DOKU_INC . 'inc/init.php';


function updateCount($label, $result, $expected, &$success, &$fail)
{
    $bold = false;
    // ALERTA! En algun cas el resultat arriba com a int i altres com a true/false
    if ($result == $expected) {
        $success++;
    } else {
        $fail++;
        $bold = true;
    }

    if ($bold) {
        echo '<b>';
    }

    echo "Resultat final $label (s'espera " . ($expected ? "TRUE" : "FALSE") . ") :" . ($result ? "TRUE" : "FALSE");

    if ($bold) {
        echo '</b>';
    }

    echo "\n";
}

// CODI DE PROVES

$tree = [
    "root" => "ag3",
    "grups" => [
        "g0" => [
            "type" => "conditions",
            "operator" => "or",
            "items" => [
                "camp1==='valor1'",
                'camp5!="valor3"',
            ]
        ],
        "g1" => [
            "type" => "conditions",
            "operator" => "",
            "items" => [
                'camp3!="valor2"'
            ]
        ],
        "ag0" => [
            "type" => "aggregation",
            "operator" => "and",
            "items" => [
                "g0",
                "g1"
            ]
        ],
        "g2" => [
            "type" => "conditions",
            "operator" => "",
            "items" => [
                's2.camp10<2021-05-01'
            ]
        ],
        "g3" => [
            "type" => "conditions",
            "operator" => "",
            "items" => [
                "camp12<camp10"
            ]
        ],
        "ag2" => [
            "type" => "aggregation",
            "operator" => "and",
            "items" => [
                "g2",
                "g3"
            ]
        ],
        "g4" => [
            "type" => "conditions",
            "operator" => "",
            "items" => [
                'main.camp1<s2.camp1'
            ]
        ],
        "ag3" => [
            "type" => "aggregation",
            "operator" => "or",
            "items" => [
                "ag0",
                "ag2",
                "g4"
            ]
        ],
        "mc0" => [
            "type" => "conditions",
            "operator" => "",
            "items" => [
                'main.camp1<s2.camp1&&camp1==="valor1"'
            ]
        ],
        "in" => [
            "type" => "conditions",
            "operator" => "",
            "items" => [
                'main.camp1 in [1, 2, 3, 4, 5]'
            ]
        ],
    ]

];
$arrays = [
    'camp1' => 'valor1',
    'camp5' => 'valor3',
    'camp3' => 'valor2',
    'camp10' => 10,
    'camp12' => 5
];
$datasource = [
    'main' => [
        'camp1' => 1,
        'camp5' => 'valor2',
        'camp3' => 'valor2',
        'camp10' => 10,
        'camp12' => 5
    ],
    's2' => [
        'camp1' => 'valor8',
        'camp10' => '2000-05-01',

    ]

];


echo('<pre>');

$success = 0;
$fail = 0;


/// TESTS
/// Test 1: g1 camp diferent a valor. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'g1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('g1', $finalResult, FALSE, $success, $fail);

/// Test 2: g2 camp < data. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'g2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('g2', $finalResult, TRUE, $success, $fail);

/// Test 3: g3 camp < camp. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'g3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('g3', $finalResult, TRUE, $success, $fail);

/// Test 4: g4 sub.camp < sub.camp. Esperat FALSE
$datasource['main']['camp1'] = 10;
$datasource['s2']['camp1'] = 0;

$root = NodeFactory::getNode($tree['grups'], 'g4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('g4', $finalResult, FALSE, $success, $fail);

/// Test 5: g4 sub.camp < sub.camp. Esperat TRUE
$datasource['main']['camp1'] = 0;
$datasource['s2']['camp1'] = 10;
$root = NodeFactory::getNode($tree['grups'], 'g4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('!g4', $finalResult, TRUE, $success, $fail);

/// Test 6: g4 sub.camp < sub.camp. Esperat TRUE
$datasource['main']['camp1'] = 0;
$datasource['s2']['camp1'] = 10;
$root = NodeFactory::getNode($tree['grups'], 'g4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('g4', $finalResult, TRUE, $success, $fail);

/// Test 7: mc0 multiple conditions sub.camp < sub.camp && subcamp == valor1. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'mc0', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('mc0', $finalResult, TRUE, $success, $fail);

/// Test 8: mc0 multiple conditions sub.camp < sub.camp && subcamp == valor1. Esperat FALSE
$arrays['camp1'] = 0;
$root = NodeFactory::getNode($tree['grups'], 'mc0', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('!mc', $finalResult, FALSE, $success, $fail);

/// Test 9: in prova in array. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'in', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('in', $finalResult, FALSE, $success, $fail);

/// Test 10: in prova in array. Esperat TRUE
$datasource['main']['camp1'] = 1;
$root = NodeFactory::getNode($tree['grups'], 'in', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('!in', $finalResult, TRUE, $success, $fail);

/// Test 11: g0 condició OR: FALSE || TRUE, expected TRUE
$arrays['camp1'] = 'valor1'; // será TRUE
$arrays['camp5'] = 'valor3'; // será FALSE
$root = NodeFactory::getNode($tree['grups'], 'g0', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('g0', $finalResult, TRUE, $success, $fail);

/// Test 12: g0 condició OR: FALSE || TRUE, expected FALSE
$arrays['camp1'] = 'valor5'; // será FALSE
$arrays['camp5'] = 'valor3'; // será FALSE
$root = NodeFactory::getNode($tree['grups'], 'g0', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('!g0', $finalResult, FALSE, $success, $fail);


/// Test 13: ag0 agregació AND, FALSE && FALSE. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'ag0', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('ag0', $finalResult, FALSE, $success, $fail);

/// Test 14: ag2 agregació AND, TRUE && TRUE. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'ag2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('ag2', $finalResult, TRUE, $success, $fail);

/// Test 15: ag3 agregació OR, FALSE || TRUE && TRUE. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'ag3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('ag3', $finalResult, TRUE, $success, $fail);


$total = $success + $fail;
echo "\n<b>Tests correctes $success/$total</b>";

echo('</pre>');