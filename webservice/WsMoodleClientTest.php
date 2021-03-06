<?php
if (!defined('DOKU_INC')) define('DOKU_INC', realpath('../../../') . '/');
if(!defined('DOKU_CONF')) define('DOKU_CONF',DOKU_INC.'conf/');

//require_once DOKU_INC.'inc/preload.php';

require_once DOKU_INC.'inc/inc_ioc/ioc_load.php';
require_once DOKU_INC.'inc/inc_ioc/ioc_project_load.php';

require_once DOKU_INC.'inc/init.php';

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$ws = new WsMoodleClient();

$ws->updateToken("", "");
$token = $ws->getToken();

print(json_encode($token));

//$token = "d2fc4e6ecd18e957ce749d6f39c7721b";
$ws->setToken($token);

$params = [
    "events" =>[
        "eventids" =>[
            0
        ],
        "groupids" =>[
            0
        ],
        "courseids" =>[
            959
        ]
    ]
];
$res = $ws->sendRequest($params, "core_calendar_get_calendar_events");

print(json_encode($res));
$ids = array();

$params = [
     "events" =>array()
];
foreach ($res->events as $item){
    if($item->eventtype=="course" && $item->courseid==959){
        $params["events"][] = ["eventid" => "{$item->id}", "repeat" => "1"];
    }
}

if(count($params["events"])>0){
    $res = $ws->sendRequest($params, "core_calendar_delete_calendar_events");
    print(json_encode($res));
}

$date = new DateTime();
$date->add(new DateInterval("P2D"));
$d = date(DATE_RSS, $date->getTimestamp());
$aparams = [
    "events" =>[
        [
            "name" => "Esdeveniment de prova ($d)",
            "timestart" => $date->getTimestamp(),
            "courseid" => "959",
            "eventtype" => "course",
            "description" => "Això és una prova per actualitzar el calendari des de la WIKI"
        ]
    ]
];
$res = $ws->sendRequest($aparams, "core_calendar_create_calendar_events");
print(json_encode($res));

$ws = new WsMoodleCalendar();

$ws->setToken($token);
//$res = $ws->getEventsForCourseId("959");
//print(json_encode($res));

$ws->deleteAllCourseEvents("959");
$events = [];
$date = new DateTime();
$date->add(new DateInterval("P2D"));
$d = date(DATE_RSS, $date->getTimestamp());
$events[] = EventMoodle::newInstanceFromAssociative(array(
            "name" => "Esdeveniment de prova ($d)",
            "timestart" => $date->getTimestamp(),
            "eventtype" => "course",
            "description" => "Això és una prova per actualitzar el calendari des de la WIKI"
        ));
$date->add(new DateInterval("P1D"));
$d = date(DATE_RSS, $date->getTimestamp());
$events[] = EventMoodle::newInstanceFromAssociative(array(
            "name" => "Esdeveniment de prova ($d)",
            "timestart" => $date->getTimestamp(),
            "eventtype" => "course"
        ));
$ws->createEventsForCourseId("959", $events);



