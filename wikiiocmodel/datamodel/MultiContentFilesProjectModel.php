<?php
/**
 * Description of MultiContentFilesProjectModel
 *
 * @author josep
 */
abstract class MultiContentFilesProjectModel extends AbstractProjectModel{

    public function createTemplateDocument($data=NULL){
        self::stCreateTemplateDocument($this, $data);
    }
    
    public function hasTemplates(){
        return true;
    }
    
    public static function stCreateTemplateDocument($obj, $data=NULL){
        $pdir = $obj->getProjectTypeDir()."metadata/plantilles/";
        $scdir = scandir($pdir);
        foreach($scdir as $file){
            if ($file !== '.' && $file !== '..' && substr($file, -4) === ".txt") {
                $plantilla = file_get_contents($pdir.$file);
                $name = substr($file, 0, -4);
                $obj->dokuPageModel->setData([PageKeys::KEY_ID => $obj->getId().":".$name,
                                               PageKeys::KEY_WIKITEXT => $plantilla,
                                               PageKeys::KEY_SUM => "generate project"]);
            }
        }
    }
    
    public function forceFileComponentRenderization($isGenerated=NULL){
        self::stForceFileComponentRenderization($this, $isGenerated);
    }
    
    /*
     * Foaça un registre de canvi del fitxer que pertany a un projecte per tal de forçar
     * una rendereització posterior. Ës útil en projectes que tenen plantilles amb camps 
     * inclosos en els seus fitxers
     */
    public static function stForceFileComponentRenderization($model, $isGenerated=NULL){
        if (!$model->getNeedGenerateAction() || $isGenerated){
            $llista = $model->llistaDeEspaiDeNomsDeDocumentsDelProjecte();
            if (!empty($llista)) {
                foreach ($llista as $p) {
                    p_set_metadata($p, array('metadataProjectChanged' => time()));
                }
            }
        }
    }
}
