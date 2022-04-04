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
    protected $repeat=1;
    protected $repeats=0;
    protected $name;
    protected $description=NULL;
    protected $timestart=1572339186;
    protected $courseId=0;
    protected $eventType="user";
    
    public static function getListFromJson($json){
        $evenst = array();
        foreach ($json as $item){
            $evenst[] = self::newInstanceFromAssociative($item);
        }        
        return $evenst;
    }
    
    public static function newInstanceFromAssociative($json=false){
        $ret = new EventMoodle();
        if($json){
            $ret->setId($json["id"])
                ->setName($json["name"])
                ->setDescription($json["description"])
                ->setTimestart($json["timestart"])
                ->setCourseId($json["courseid"])
                ->setEventType($json["eventtype"]);
        }
        return $ret;
    }
    
    public static function newInstanceFromObject($json=false){
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

class WsMoodleSession extends WsMoodleClient {

    //Keep the users session alive
    function refreshUserSession($user) {
        $this->setWsFunction("core_user_get_users_by_field");
        $params = ['field' => "username",
                   'values' => [$user]
                  ];
        $json = $this->sendRequest($params, "core_user_get_users_by_field");

        if ($this->requestError != NULL){
            throw new Exception($json->message);
        }else{
            $ret = $json[0]->id;
        }

        return $ret;
    }

    //Keep the users session alive
    // ALERT: Aquest webservice no està implementat al moodle
    function keepUserSessionAlive($id=0) {
        //https://docs.moodle.org/all/es/Manejo_de_la_sesi%C3%B3n#File_session_driver
        //https://docs.moodle.org/dev/Web_service_API_functions
        $this->setWsFunction("core_session_touch");
        $params = ['userid' => $id];
        $json = $this->sendRequest($params, "core_session_touch");

        if ($this->requestError != NULL){
            throw new Exception($json->message);
        }else{
            $ret = $json;
        }
        return $ret;
    }

    //Count the seconds remaining in this session
    // ALERT: Aquest webservice no està implementat al moodle
    function timeSessionRemaining($id=0) {
        $this->setWsFunction("core_session_time_remaining");
        $params = ['userid' => $id];
        $json = $this->sendRequest($params, "core_session_time_remaining");

        if ($this->requestError != NULL){
            throw new Exception($json->message);
        }else{
            $ret = $json;
        }
        return $ret;
    }

}

class WsMoodleCalendar extends WsMoodleClient{

    public function getEventsForCourseId($courseId){
        $params = [
            "events" =>[
                "courseids" =>array($courseId)
            ]
        ];   
        $json = $this->sendRequest($params, "core_calendar_get_calendar_events");     
        if($this->requestError!=NULL){
            //Excepció
            throw new WsMoodleCalendarException($json);
        }else{
            $ret = $json->events;
        }
        return $ret;
    }
    
    public function getEvents($courseIds=array(), $groupIds=array(), $eventIds=array()){
        if((!is_array($courseIds) || count($courseIds)==0) && (!is_array($groupIds) || count($groupIds)==0) && (!is_array($eventIds) || count($eventIds)==0)){
            return "";
        }
        $params = [
            "events" =>[
                "eventids" =>$eventIds,
                "groupids" =>$groupIds,
                "courseids" =>$courseIds
            ]
        ];   
        $json = $this->sendRequest($params, "core_calendar_get_calendar_events");     
        if($this->requestError!=NULL){
            //Excepció
            throw new WsMoodleCalendarException($json);
        }else{
            $ret = EventMoodle::getListFromJson($json);
        }
        return $res;
    }
    
    public function createEventsForCourseId($courseId, $events=array()){
        if(!is_array($events) || count($events)==0){
            return "";
        }
        
        $params = [
             "events" =>array()
        ];
        foreach ($events as $item){
            $params["events"][] = [
                "name" => $item->getName(), 
                "description" => $item->getDescription(), 
                "timestart" => $item->getTimestart(),
                "courseid" => $courseId,
                "eventtype" => $item->getEventType()
            ];
        }
        $json = $this->sendRequest($params, "core_calendar_create_calendar_events");
        if($this->requestError!=NULL){
            //Excepció
            throw new WsMoodleCalendarException($this->requestError);
        }else{
            $ret = $json->events;
        }
        return $res;        
    }
    
    public function createEvents($events=array()){
        if(!is_array($events) || count($events)==0){
            return "";
        }
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
        $json = $this->sendRequest($params, "core_calendar_create_calendar_events");
        if($this->requestError!=NULL){
            //Excepció
            throw new WsMoodleCalendarException($json);
        }else{
            $ret = EventMoodle::getListFromJson($json->events);
        }
        return $res;
    }
    
    public function deleteCourseEventsFromEvents($courseId, $events=array()){
        if(!is_array($events) || count($events)==0){
            return "";
        }
        $params = [
             "events" =>array()
        ];
        
        foreach ($events as $item){
            if($item->eventtype=="course" && $item->courseid==$courseId){
                $params["events"][] = ["eventid" => "{$item->id}", "repeat" => ($item->repeat==null?"1":$item->repeat)];
            }
        }
        if(count($params["events"])>0){
            $this->sendRequest($params, "core_calendar_delete_calendar_events");        
            if($this->requestError!=NULL){
                throw new WsMoodleCalendarException($this->requestError);
            }
        }
    }
    
    public function deleteEventsFromIds($eventIds=array()){
        if(!is_array($eventIds) || count($eventIds)==0){
            return "";
        }
        $params = [
             "events" =>array()
        ];
        foreach ($eventIds as $item){
            $params["events"][] = ["eventid" => "{$item}", "repeat" => "1"];
        }
        $this->sendRequest($params, "core_calendar_delete_calendar_events");        
        if($this->requestError!=NULL){
            //Excepció
            throw new WsMoodleCalendarException($this->requestError);
        }
    }

    public function deleteAllCourseEvents($courseId=0){
        $resp = $this->getEventsForCourseId($courseId);
        $this->deleteCourseEventsFromEvents($courseId, $resp);        
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
        $this->urlBase = $urlBase;
        $this->furlToken= $furlToken;
        $this->furl= $furl;
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
        try {
            $content = file_get_contents($url, false, $context);
            $resp = json_decode($content);
            if ($resp->exception){
                $this->requestError = $resp;
            }
        }catch (Exception $ex) {
            $resp = $ex;
            $this->requestError = $ex;
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
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        if($data){
            $context["http"]["header"] .= "Content-Length: ".strlen($data)."\r\n";
            $context["http"]["content"] = $data;
        }

        return stream_context_create($context);
    }
}


