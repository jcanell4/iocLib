<?php
/**
 * Description of MoodleProjectModel
 * @author josep
 */
if (!defined('DOKU_INC')) die();

abstract class MoodleUniqueContentFilesProjectModel extends MoodleProjectModel{
    public function createTemplateDocument($data=NULL){
        UniqueContentFileProjectModel::stCreateTemplateDocument($this, $data);
    }
    
    public function forceFileComponentRenderization($isGenerated=NULL){
        UniqueContentFileProjectModel::stForceFileComponentRenderization($this, $isGenerated);
    }
    
    public function getProjectDocumentName() {
        return UniqueContentFileProjectModel::stGetProjectDocumentName($this);
    }
}
