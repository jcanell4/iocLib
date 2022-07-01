<?php
/**
 * Description of MultiContentFilesProjectModel
 *
 * @author josep
 */
abstract class UniqueContentFileProjectModel extends AbstractProjectModel{
    public function createTemplateDocument($data=NULL){
        self::stCreateTemplateDocument($this, $data);
    }
    
    public function hasTemplates(){
        return true;
    }
    
    public static function stCreateTemplateDocument($obj, $data=NULL){
        $pdir = $obj->getProjectTypeDir()."metadata/plantilles/";
        $file = $obj->getTemplateContentDocumentId() . ".txt";
        $plantilla = file_get_contents($pdir.$file);
        $name = substr($file, 0, -4);
        $destino = $obj->getContentDocumentId($name);
        $obj->getDokuPageModel()->setData([PageKeys::KEY_ID => $destino,
                                       PageKeys::KEY_WIKITEXT => $plantilla,
                                       PageKeys::KEY_SUM => "generate project"]);
    }
    
    public function forceFileComponentRenderization($isGenerated=NULL){
        self::stForceFileComponentRenderization($this, $isGenerated);
    }
    
    public function getProjectDocumentName() {
        return self::stGetProjectDocumentName($this);
    }
    
    /*
     * Foaça un registre de canvi del fitxer que pertany a un projecte per tal de forçar
     * una rendereització posterior. Ës útil en projectes que tenen plantilles amb camps 
     * inclosos en els seus fitxers
     */
    public static function stForceFileComponentRenderization($model, $isGenerated=NULL){
        if ($isGenerated || !$model->getNeedGenerateAction()){
            $ns_continguts = $model->getContentDocumentId();
            p_set_metadata($ns_continguts, array('metadataProjectChanged' => time()));
        }
    }
    
    public static function stGetProjectDocumentName($model){
        $ns_continguts = $model->getContentDocumentId();
        $lastPos = strrpos($ns_continguts, ':');

        if ($lastPos) {
            $ns_continguts = substr($ns_continguts, $lastPos+1);
        }
        return $ns_continguts;
    }

    /**
     * Construye la lista de ficheros a partir del array recibido
     * @return array con los nombres de los ficheros
     */
    private function _constructArrayFileNames($name, $metaDataFtpSender=NULL) {
        if ($metaDataFtpSender) {
            $ret = array();
            $output_filename = str_replace(":", "_", $name);
            foreach ($metaDataFtpSender as $value) {
                $suff = (empty($value['suffix'])) ? "" : "_{$value['suffix']}";
                $ret[] = "${output_filename}${suff}.{$value['type']}";
            }
        }
        return $ret;
    }

}
