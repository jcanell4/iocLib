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
     * Comprova si els fitxers 'HTML export' s'han enviat al servidor FTP
     * @return string HTML per a les metadades
     */
    public function get_ftpsend_metadata($useSavedTime=TRUE) {
        $mdFtpSender = $this->getMetaDataFtpSender();
        $connData = $this->getFtpConfigData($mdFtpSender[ProjectKeys::KEY_FTPID]);
        $html = '';
        $ruta = str_replace(':', '/', $this->id)."/";
        $fileNames = $this->_constructArrayFileNames($this->id, $mdFtpSender['files']);

        $n = 0;
        foreach ($mdFtpSender['files'] as $ofile) {
            $filename = $fileNames[$n];
            $path = ($ofile['local']==='mediadir') ? WikiGlobalConfig::getConf('mediadir')."/$ruta" : $ofile['local'];
            $file = "$path$filename";
            if (@file_exists($file)) {
                $savedtime = $this->projectMetaDataQuery->getProjectSystemStateAttr("ftpsend_timestamp");
                $filetime = filemtime($file);
                $fileexists = (!$useSavedTime || ($savedtime === $filetime));
            }
            if ($fileexists) {
                $type = $ofile['type'];
                $unzip = in_array(1, $ofile['action']);  //0:action tipo copy, 1:action tipo unzip
                $data = date("d/m/Y H:i:s", $filetime);
                $class = "mf_$type";
                $index = $filename;
                $linkRef = $filename;
                $rDir = (empty($ofile['remoteDir'])) ? (empty($mdFtpSender['remoteDir'])) ? $connData["remoteDir"] : $mdFtpSender['remoteDir'] : $ofile['remoteDir'];
                $rDir .= ($unzip) ? $ruta.pathinfo($file, PATHINFO_FILENAME)."/" : $ruta;
                $url = "{$connData['remoteUrl']}{$rDir}{$index}";
                $html.= '<p><span id="ftpsend" style="word-wrap: break-word;">';
                $html.= '<a class="media mediafile '.$class.'" href="'.$url.'" target="_blank">'.$linkRef.'</a> ';
                $html.= '<span style="white-space: nowrap;">'.$data.'</span>';
                $html.= '</span></p>';
                $n++;
            }else {
                $html.= '<span id="ftpsend">';
                $html.= '<p class="media mediafile '.$class.'">No hi ha cap fitxer pujat al FTP</p>';
                $html.= '</span>';
                break;
            }
        }
        return $html;
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

    /**
     * Obtiene la lista de ficheros, y sus propiedades, (del configMain.json) que hay que enviar por FTP
     * @return array
     */
    public function filesToExportList() {
        $ret = array();
        $connData = $this->getFtpConfigData();
        $metadata = $this->getMetaDataFtpSender();
        $ruta = str_replace(':', '/', $this->id)."/";
        if (!empty($metadata["files"])) {
            foreach ($metadata["files"] as $n => $objFile) {
                $suff = (empty($objFile['suffix'])) ? "" : "_{$objFile['suffix']}";
                $path = ($objFile['local']==='mediadir') ? WikiGlobalConfig::getConf('mediadir')."/$ruta" : $objFile['local'];
                if (($dir = @opendir($path))) {
                    while ($file = readdir($dir)) {
                        if (!is_dir("$path/$file") && preg_match("/.+${suff}\.{$objFile['type']}$/", $file) ) {
                            $ret[$n]['file'] = $file;
                            $ret[$n]['local'] = $path;
                            $ret[$n]['action'] = $objFile['action'];
                            $unzip = in_array(1, $objFile['action']);  //0:action tipo copy, 1:action tipo unzip
                            $rBase = (empty($objFile['remoteBase'])) ? (empty($metadata['remoteBase'])) ? $connData["remoteBase"] : $metadata['remoteBase'] : $objFile['remoteBase'];
                            $rDir  = (empty($objFile['remoteDir'])) ? (empty($metadata['remoteDir'])) ? $connData["remoteDir"] : $metadata['remoteDir'] : $objFile['remoteDir'];
                            $rDir .= ($unzip) ? $ruta.pathinfo($file, PATHINFO_FILENAME)."/" : $ruta;
                            $ret[$n]['remoteBase'] = $rBase;
                            $ret[$n]['remoteDir'] = $rDir;
                        }
                    }
                }
            }
        }
        return $ret;
    }

}
