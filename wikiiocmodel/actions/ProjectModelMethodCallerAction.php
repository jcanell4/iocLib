<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MethodCaller
 *
 * @author josep
 */

if (!defined("DOKU_INC")) die();


class ProjectModelMethodCallerAction extends ProjectAction{
    
    protected function responseProcess() {
        $model = $this->getModel();
        
        return $model->callMethod($this->params[ProjectKeys::KEY_COMMAND], $this->params[ProjectKeys::KEY_PARAMETERS]);
    }
}
