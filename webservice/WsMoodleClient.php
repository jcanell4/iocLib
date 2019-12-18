<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WsClient
 *
 * @author josep
 */
class EventMoodle{
    protected $id=NULL;
    protected $repeat=0;
    protected $repeats=0;
    protected $name;
    protected $description=NULL;
    protected $timestart=1572339186;
    protected $courseId=0;
    protected $eventType="user";
    
    public static function getListFromJson($json){
        $evenst = array();
        foreach ($json as $item){
            $evenst[] = self::newInstance($item);
        }        
        return $evenst;
    }
    
    public static function newInstance($json=false){
        $ret = new EventMoodle();
        if($json){
            $ret->setId($json->id)
                ->setName($json->name)
                ->setDescription($json->description)
                ->setTimestart($json->timestart)
                ->setCourseId($json->courseid)
                ->setEventType($json->eventtype);
        }
        return $ret;
    }
    
    public function setId($id){
        $this->id = $id;
        return $this;
    }
    
    public function setName($name){
        $this->name = $name;
        return $this;
    }
    
    public function setDescription($description){
        $this->description = $description;
        return $this;
    }
    
    public function setTimestart($timestart){
        $this->timestart = $timestart;
        return $this;
    }
    
    public function setRepeat($repeat){
        $this->repeat= $repeat;
        return $this;
    }
    
    public function setRepeats($repeats){
        $this->repeats= $repeats;
        return $this;
    }
    
    public function setCourseId($courseId){
        $this->courseId= $courseId;
        return $this;
    }
    
    public function setEventType($eventType){
        $this->eventType= $eventType;
        return $this;
    }
    
    public function getId(){
        return $this->id;
    }
    
    public function getCourseId(){
        return $this->courseId;
    }
    
    public function getDescription(){
        return $this->description;
    }
    
    public function getEventType(){
        return $this->eventType;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function getRepeat(){
        return $this->repeat;
    }
    
    public function getRepeats(){
        return $this->repeats;
    }
    
    public function getTimestart(){
        return $this->timestart;
    }
}

class WsMoodleCalendar extends WsMoodleClient{
    
    public function getEvents($courseIds=array(), $groupIds=array(), $eventIds=array()){
        $params = [
            "events" =>[
                "eventids" =>$eventIds,
                "groupids" =>$groupIds,
                "courseids" =>$courseIds
            ]
        ];   
        $res = $ws->sendRequest($params, "core_calendar_get_calendar_events");
        return $res;
    }
    
    public function createEvents($events=array()){
        $params = [
             "events" =>array()
        ];
        foreach ($events as $item){
            $params["events"][] = [
                "name" => $item->getName(), 
                "description" => $item->getDescription(), 
                "timestart" => $item->getTimestart(),
                "courseid" => $item->getCourseId(),
                "eventType" => $item->getEventType()
            ];
        }
        $res = $ws->sendRequest($params, "core_calendar_create_calendar_events");
        return $res;
    }
    
    public function deleteEventsFromEvents($events=array()){
        $params = [
             "events" =>array()
        ];
        foreach ($events as $item){
            $params["events"][] = ["eventid" => "{$item->id}", "repeat" => $item->repeat];
        }
        $res = $ws->sendRequest($params, "core_calendar_delete_calendar_events");        
        return $res;
    }
    
    public function deleteEventsFromIds($eventIds=array()){
        $params = [
             "events" =>array()
        ];
        foreach ($eventIds as $item){
            $params["events"][] = ["eventid" => "{$item}", "repeat" => "1"];
        }
        $res = $ws->sendRequest($params, "core_calendar_delete_calendar_events");        
        return $res;
    }
    
}

class WsMoodleClient {
    const MOODLEWSRESTFORMAT= "json";
    protected $token = NULL;
    protected $wsFunction;
    protected $urlBase = "https://ioc.xtec.cat/campus";
    protected $furlToken = "/login/token.php";
    protected $furl ="/webservice/rest/server.php";
    protected $urlParams=array();
    protected $requestError = NULL;

    public function init($urlBase, $furlToken, $furl, $urlParams=false){
        $this->url = $url;
        if(is_array($urlParams)){
            $this->urlParams = array_merge([], $urlParams);
        }
    }

    public function setToken($token){
        $this->token=$token;
    }

    public function setWsFunction($wsFunction){
        $this->wsFunction = $wsFunction;
    }

    public function setUrlParams(array $urlParams){
        if(is_array($urlParams)){
            $this->urlParams = array_merge([], $urlParams);
        }
    }

    public function sendRequest(array $wsParams, $wsFunction=FALSE){
        $url = $this->urlBase.$this->furl;
        if(!$wsFunction){
            $wsFunction = $this->wsFunction;
        }
        $query = http_build_query(["wstoken" => $this->token, "wsfunction" => $wsFunction, "moodlewsrestformat" => self::MOODLEWSRESTFORMAT], "", "&");

        $postData = $this->getStrData($wsParams);

        return $this->_sendRequest($url, "", $query."&".$postData);

    }

    public function updateToken($user, $pass){
        $url = $this->urlBase. $this->furlToken;
        $query = http_build_query(["username" => $user, "password" => $pass, "service" => "moodle_mobile_app"], "", "&");
        $result = $this->_sendRequest($url, "", $query);
        $this->requestError = ($result->error) ? $result : NULL;
        $this->setToken($result->token);
    }

    public function getToken(){
        return $this->token;
    }

    protected function _sendRequest($url, $query="", $postData=FALSE){
        $this->requestError = NULL;
        if ($query){
            $url = $url."?".$query;
        }
        if ($postData){
            $context = $this->getContext("POST", $postData);
        }else{
            $context = $this->getContext("GET");
        }
        $resp = json_decode(file_get_contents($url, false, $context));      
        if($resp->exception){
            $this->requestError = $resp;
        }
        return $resp;
    }

    protected function getStrData($data){
        if(is_array($data)){
            $query  = http_build_query($data, "","&");
            $query = preg_replace(["/%5B/", "/%5D/"], ["[", "]"], $query);
        }else{
            $query = $data;
        }
        return $query;
    }

    protected function getContext($method="GET", $data=FALSE){
        $context = [
            'http' => [
                'method' => $method,
                'header' => "Content-type: application/x-www-form-urlencoded\r\n"
//            ],
//            'ssl' => [
//                'verify_peer' => false,
//                'verify_peer_name' => false
            ]
        ];
        if($data){
            $context["http"]["header"] .= "Content-Length: ".strlen($data)."\r\n";
            $context["http"]["content"] = $data;
        }

        return stream_context_create($context);
    }
}


