<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FtpSender
 *
 * @author professor
 */
class FtpSender{
    private $ftpObjectToSendList;
    
    public function __construct() {
        $this->ftpObjectToSendList = array();
    }
    
    public function addObjectToSendList($file, $remoteBase, $remoteDir, $action=0){
        $this->ftpObjectToSendList []= new FtpObjectToSend($file, $remoteBase, $remoteDir, $action);
    }
    
    protected function process() {
        $response;
        
        //Codificar l'enviament de cada fitxer de la llista d'acorp amb els seus paràmetres
        //tractar  les respostes a la variable $response per tal de poder informar del que 
        //ha passat duarnt la connexió
        
        return $response;
    }
}

class FtpObjectToSend{
    const COPY_ACTION = 0;
    const UNZIP_AND_COPY_ACTION = 1;

    private $file;
    private $remoteBase;
    private $remoteDir;
    private $action;
    
    public function __construct($file, $remoteBase, $remoteDir, $action=0) {
        $this->file = $file;
        $this->remoteBase= $remoteBase;
        $this->remoteDir= $remoteDir;
        $this->action= $action;
    }
    
    public function getFile(){
        return $this->file;
    }

    public function getRemoteBase(){
        return $this->remoteBase;
    }

    public function getRemoteDir(){
        return $this->remoteDir;
    }

    public function getAction(){
        return $this->action;
    }
}
