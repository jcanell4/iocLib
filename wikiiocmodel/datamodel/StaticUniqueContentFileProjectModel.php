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
class StaticUniqueContentFileProjectModel{
    public static function createTemplateDocument($obj, $data=NULL){
        $pdir = $obj->getProjectMetaDataQuery()->getProjectTypeDir()."metadata/plantilles/";
        // TODO: $file ha de ser el nom del fitxer de la plantilla, amb extensió?
        if($data==NULL){
            $file = $obj->getTemplateContentDocumentId("continguts") . ".txt";
        }else{
            $file = $obj->getTemplateContentDocumentId($data) . ".txt";            
        }

        $plantilla = file_get_contents($pdir.$file);
        $name = substr($file, 0, -4);
        $destino = $obj->getContentDocumentId($name);
        $obj->getDokuPageModel()->setData([PageKeys::KEY_ID => $destino,
                                       PageKeys::KEY_WIKITEXT => $plantilla,
                                       PageKeys::KEY_SUM => "generate project"]);
    }
}
