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
class WsMoodleClient {
    protected $token = NULL;
    protected $wsFunction;
    protected $moodlewsrestformat='json';
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

    public function sendRequest(array $wsParams, $wsFunction=FALSE, $moodlewsrestformat='json'){
        $url = $this->urlBase.$this->furl;
        if(!$wsFunction){
            $wsFunction = $this->wsFunction;
        }
        $query = http_build_query(["wstoken" => $this->token, "wsfunction" => $wsFunction, "moodlewsrestformat" => $moodlewsrestformat], "", "&");

        $postData = $this->getStrData($wsParams);

        return $this->_sendRequest($url, "", $query."&".$postData);

    }

    public function updateToken($user, $pass){
        $url = $this->urlBase. $this->furlToken;
        $query = http_build_query(["username" => $user, "password" => $pass, "service" => "moodle_mobile_app"], "", "&");
        $result = json_decode($this->_sendRequest($url, "", $query));
        $this->requestError = ($result->error) ? $result : NULL;
        $this->setToken($result->token);
    }

    public function getToken(){
        return $this->token;
    }

    protected function _sendRequest($url, $query="", $postData=FALSE){
        $this->token = NULL;
        if ($query){
            $url = $url."?".$query;
        }
        if ($postData){
            $context = $this->getContext("POST", $postData);
        }else{
            $context = $this->getContext("GET");
        }
        return file_get_contents($url, false, $context);
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


