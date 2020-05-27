<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MultiContentFilesProjectModel
 *
 * @author josep
 */
abstract class UniqueContentFileProjectModel extends AbstractProjectModel{
    public function createTemplateDocument($data=NULL){
        StaticUniqueContentFileProjectModel::createTemplateDocument($this, $data);
    }
}
