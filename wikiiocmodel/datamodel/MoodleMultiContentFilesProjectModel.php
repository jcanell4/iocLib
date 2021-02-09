<?php
/**
 * Description of MoodleProjectModel
 * @author josep
 */
if (!defined('DOKU_INC')) die();

abstract class MoodleMultiContentFilesProjectModel extends MoodleProjectModel{
    public function hasTemplates(){
        return true;
    }
    
    public function createTemplateDocument($data=NULL){
        MultiContentFilesProjectModel::stCreateTemplateDocument($this, $data);
    }
    
    public function forceFileComponentRenderization($isGenerated=NULL){
        MultiContentFilesProjectModel::stForceFileComponentRenderization($this, $isGenerated);
    }
}
