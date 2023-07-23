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
        echo '<span><b style="color: red">';
    } else {
        echo '<span style="color: green">';
    }

    echo "Resultat final $label (s'espera " . ($expected ? "TRUE" : "FALSE") . ") :" . ($result ? "TRUE" : "FALSE");

    if ($bold) {
        echo '</b>';
    }

    echo "</span>";
    echo "\n";
}

// CODI DE PROVES

$tree = [
    "root" => "ag3",
    "grups" => [
        "fun01" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'ARRAY_GET_SUM("taulaDadesUnitats", "hores", "unitat", 4)==ARRAY_GET_SUM("calendari", "hores", "unitat", 4)'
            ]
        ],
        "fun02" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'SEARCH_VALUE(25, "[\"clave\"=>27, \"patata\"=>23]", "clave")'
            ]
        ],
        "g0" => [
            "type" => "conditions",
            "connector" => "or",
            "elements" => [
                "camp1==='valor1'",
                'camp5!=="valor3"',
            ]
        ],
        "g1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp3!=="valor2"'
            ]
        ],
        "ag0" => [
            "type" => "aggregation",
            "connector" => "and",
            "elements" => [
                "g0",
                "g1"
            ]
        ],
        "g2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                's2.camp10<2021-05-01'
            ]
        ],
        "g3" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                "camp12<camp10"
            ]
        ],
        "ag2" => [
            "type" => "aggregation",
            "connector" => "and",
            "elements" => [
                "g2",
                "g3"
            ]
        ],
        "g4" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'main.camp1<s2.camp1'
            ]
        ],
        "ag3" => [
            "type" => "aggregation",
            "connector" => "or",
            "elements" => [
                "ag0",
                "ag2",
                "g4"
            ]
        ],
        "mc0" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'main.camp1<s2.camp1&&camp1==="valor1"'
            ]
        ],
        "in" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'main.camp1 in ["1", "2", "3", "4", camp12]'
            ]
        ],
        "in2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'main.camp1 in ["0", "2", "3", "4", camp12]'
            ]
        ],
        "object1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj1#unitat formativa==="1"'
            ]
        ],
        "object2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj1#nom==="Introducció al programari de base i a la virtualització"'
            ]
        ],
        "object3" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj1#nom==="null"'
            ]
        ],
        "aObject1" => [
            "type" => "aggregation",
            "connector" => "and",
            "elements" => [
                'object1', 'object3'
            ]
        ],
        "aObject2" => [
            "type" => "aggregation",
            "connector" => "or",
            "elements" => [
                'object1', 'object3'
            ]
        ],
        "object4" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj1#nom===main.obj1#nom'
            ]
        ],
        "object5" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj1#nom===s2.obj2#nom'
            ]
        ],
        "l1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp20===100'
            ]
        ],
        "l2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp20===0'
            ]
        ],
        "l3" => [ // TRUE
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                '0===0'
            ]
        ],
        "l4" => [ // FALSE
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                '0===10'
            ]
        ],
        "l5" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                '100===camp20'
            ]
        ],
        "l6" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                '0===camp20'
            ]
        ],

        "l7" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp21===TRUE'
            ]
        ],
        "l8" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp21===true'
            ]
        ],
        "l9" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp21===FALSE'
            ]
        ],
        "l10" => [ // FALSE
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp21===false'
            ]
        ],
        "l11" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'true===camp21'
            ]
        ],
        "l12" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'false===camp21'
            ]
        ],
        "r1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp30[0]===10'
            ]
        ],
        "r2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp30[1]===10'
            ]
        ],
        "r3" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj30[0]#nom==="Introducció al programari de base i a la virtualització"'
            ]
        ],
        "r4" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj30[1]#nom==="Introducció al programari de base i a la virtualització"'
            ]
        ],
        "r5" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj30[0]#nom===obj30[0]#nom'
            ]
        ],
        "r6" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'obj30[0]#nom===obj30[1]#nom'
            ]
        ],
        "f1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'MAX([1, 2])===2'
            ]
        ],
        "f2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'MAX([1, camp10])===1'
            ]
        ],
        "f3" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'SUMA(2, 3)===5'
            ]
        ],
        "f4" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(camp1)===true'
            ]
        ],
        "f5" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(empty)===true'
            ]
        ],
        "f6" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(camp1)===false'
            ]
        ],
        "f7" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(empty)===false'
            ]
        ],
        "f8" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(camp1)'
            ]
        ],
        "f9" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(empty)'
            ]
        ],
        "f10" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(no_existeix)===true'
            ]
        ],
        "f11" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(no_existeix)===false'
            ]
        ],
        "f12" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY([])===true'
            ]
        ],
        "f13" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY([])===false'
            ]
        ],
        "f14" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IN_ARRAY(3, [1, 2, 3])'
            ]
        ],
        "f15" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IN_ARRAY(19, [1, 2, 3])'
            ]
        ],
        "f16" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY("")'
            ]
        ],
        "array-x1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'main.camp1 in [1, 2, 3, 4, camp12]'
            ]
        ],
        "array-x2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                '"hola, món" in ["hola, món", "no, adeu", "3", 4, camp12]'
            ]
        ],
        "array-x3" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                '"no" in ["no, adeu", "3", 4, camp12]'
            ]
        ],
        "not-1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY("")!=true'
            ]
        ],
        "not-2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY("aaa")!==true'
            ]
        ],
        "not-3" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY("")!==true'
            ]
        ],
        "not-4" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY("aaa")!==true'
            ]
        ],
        "workflow-1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'management.workflow#currentState!=="validated"'
            ]
        ],
        "workflow-2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'management.workflow#currentState!=="foobar"'
            ],
        ],
        "workflow-3" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'management.workflow#currentState==="validated"'
            ]
        ],
        "sr1" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp300[0]===10'
            ]
        ],
        "sr2" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'camp300[1]===10'
            ]
        ],
        "sr3" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(camp300)!==true'
            ]
        ],
        "sr4" => [
            "type" => "conditions",
            "connector" => "",
            "elements" => [
                'IS_STR_EMPTY(array_buit)!==true'
            ]
        ],
    ]

];
$arrays = [
    'camp1' => 'valor1',
    'camp5' => 'valor3',
    'camp3' => 'valor2',
    'empty' => '',
    'camp10' => 10,
    'camp12' => 5,
    'camp20' => 100,
    'camp21' => TRUE,
    'camp30' => [10, 20, 30],
    'camp300' => "[10, 20, 30]",
    'obj1' => '{"unitat formativa":"1","nucli formatiu":"1","nom":"Introducció al programari de base i a la virtualització","hores":"16","unitat al pla de treball":"1"}',
    'obj30' => [
        '{"unitat formativa":"1","nucli formatiu":"1","nom":"Introducció al programari de base i a la virtualització","hores":"16","unitat al pla de treball":"1"}',
        '{"unitat formativa":"1","nucli formatiu":"3","nom":"Administració de programari de base lliure","hores":"30","unitat al pla de treball":"3"}',
    ],
    'aObj51' =>[
        ["nom"=>"a1", "filtre"=>0 ,"valor"=>5],
        ["nom"=>"a2", "filtre"=>1 ,"valor"=>10],
        ["nom"=>"a3", "filtre"=>0 ,"valor"=>15]
    ],
    'array_buit' => "[]",
];
$datasource = [
    'management' => [
        'workflow' => '{"currentState":"validated"}'
    ],
    'main' => [
        'camp1' => 1,
        'camp5' => 'valor2',
        'camp3' => 'valor2',
        'camp10' => 10,
        'camp12' => 5,
        'camp20' => 100,
        'camp21' => TRUE,
        'camp30' => [10, 20, 30],
        'obj1' => '{"unitat formativa":"1","nucli formatiu":"1","nom":"Introducció al programari de base i a la virtualització","hores":"16","unitat al pla de treball":"1"}',
        'obj30' => [
            '{"unitat formativa":"1","nucli formatiu":"1","nom":"Introducció al programari de base i a la virtualització","hores":"16","unitat al pla de treball":"1"}',
            '{"unitat formativa":"1","nucli formatiu":"3","nom":"Administració de programari de base lliure","hores":"30","unitat al pla de treball":"3"}',
        ],
        'aObj50' =>[
            '{"nom":"a1", "filtre":0 ,"valor":5}',
            '{"nom":"a2", "filtre":1 ,"valor":10}',
            '{"nom":"a3", "filtre":0 ,"valor":15}',
        ],
    ],
    's2' => [
        'camp1' => 'valor8',
        'camp10' => '2000-05-01',
        'obj2' => '{"unitat formativa":"1","nucli formatiu":"2","nom":"Instal·lació de programari de base lliure i de propietat","hores":"16","unitat al pla de treball":"2"},{"unitat formativa":"1","nucli formatiu":"3","nom":"Administració de programari de base lliure","hores":"30","unitat al pla de treball":"3"}'
    ]

];


echo('<pre>');

$success = 0;
$fail = 0;


/// TESTS

$root = NodeFactory::getNode($tree['grups'], 'fun01', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('fun01', $finalResult, TRUE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'fun02', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('fun02', $finalResult, FALSE, $success, $fail);

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


/// Test 16: object1. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'object1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('object1', $finalResult, TRUE, $success, $fail);

/// Test 17: object2. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'object2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('object2', $finalResult, TRUE, $success, $fail);

/// Test 18: object3. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'object3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('object3', $finalResult, FALSE, $success, $fail);

/// Test 19: aObject1. TRUE && FALSE. Esperat FALS
$root = NodeFactory::getNode($tree['grups'], 'aObject1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('aObject1', $finalResult, FALSE, $success, $fail);

/// Test 20: aObject2. TRUE || FALSE. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'aObject2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('aObject2', $finalResult, TRUE, $success, $fail);

/// Test 21: object4. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'object4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('object4', $finalResult, TRUE, $success, $fail);

/// Test 22: object5. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'object5', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('object5', $finalResult, FALSE, $success, $fail);


/// Test 23: literals. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'l1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l1', $finalResult, TRUE, $success, $fail);

/// Test 24: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'l2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l2', $finalResult, FALSE, $success, $fail);

/// Test 25: literals. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'l3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l3', $finalResult, TRUE, $success, $fail);

/// Test 26: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'l4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l4', $finalResult, FALSE, $success, $fail);

/// Test 27: literals. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'l5', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l5', $finalResult, TRUE, $success, $fail);

/// Test 28: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'l6', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l6', $finalResult, FALSE, $success, $fail);

/// Test 29: literals. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'l7', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l7', $finalResult, TRUE, $success, $fail);

/// Test 30: literals. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'l8', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l8', $finalResult, TRUE, $success, $fail);

/// Test 31: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'l9', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l9', $finalResult, FALSE, $success, $fail);

/// Test 32: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'l10', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l10', $finalResult, FALSE, $success, $fail);

/// Test 33: literals. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'l11', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l11', $finalResult, TRUE, $success, $fail);

/// Test 34: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'l12', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('l12', $finalResult, FALSE, $success, $fail);

/// Test 35: literals. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'r1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('r1', $finalResult, TRUE, $success, $fail);

/// Test 36: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'r2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('r2', $finalResult, FALSE, $success, $fail);

/// Test 37: literals. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'r3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('r3', $finalResult, TRUE, $success, $fail);

/// Test 38: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'r4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('r4', $finalResult, FALSE, $success, $fail);

/// Test 39: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'r5', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('r5', $finalResult, TRUE, $success, $fail);

/// Test 40: literals. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'r6', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('r6', $finalResult, FALSE, $success, $fail);

/// Test 41: funcions. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'f1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f1', $finalResult, TRUE, $success, $fail);

/// Test 42: funcions. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'f2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f2', $finalResult, FALSE, $success, $fail);

/// Test 42b: funcions. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'f3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f3', $finalResult, TRUE, $success, $fail);

/// Test 43: Més arrays. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'array-x1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('array-x1', $finalResult, TRUE, $success, $fail);

/// Test 44: Més arrays. Esperat TRUE
$root = NodeFactory::getNode($tree['grups'], 'array-x2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('array-x2', $finalResult, TRUE, $success, $fail);

/// Test 45: Més arrays. Esperat FALSE
$root = NodeFactory::getNode($tree['grups'], 'array-x3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('array-x3', $finalResult, FALSE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f4', $finalResult, FALSE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f5', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f5', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f6', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f6', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f7', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f7', $finalResult, FALSE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'f8', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f8', $finalResult, FALSE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f9', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f9', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f10', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f10', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f11', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f11', $finalResult, FALSE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f12', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f12', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f13', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f13', $finalResult, FALSE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f14', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f14', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f15', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f15', $finalResult, FALSE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'f16', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('f16', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'not-1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('not-1', $finalResult, FALSE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'not-2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('not-2', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'not-3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('not-3', $finalResult, FALSE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'not-4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('not-4', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'workflow-1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('workflow-1', $finalResult, FALSE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'workflow-2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('workflow-2', $finalResult, TRUE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'workflow-3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('workflow-3', $finalResult, TRUE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'sr1', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('sr1', $finalResult, TRUE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'sr2', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('sr2', $finalResult, FALSE, $success, $fail);

$root = NodeFactory::getNode($tree['grups'], 'sr3', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('sr3', $finalResult, TRUE, $success, $fail);


$root = NodeFactory::getNode($tree['grups'], 'sr4', $arrays, $datasource);
$finalResult = $root->getValue();
updateCount('sr4', $finalResult, FALSE, $success, $fail);


$total = $success + $fail;
echo "\n<b>Tests correctes $success/$total</b>";

echo('</pre>');