<?php
/**
 * Description of MultiContentFilesProjectModel
 *
 * @author josep
 */
abstract class MultiContentFilesProjectModel extends AbstractProjectModel{

    public function createTemplateDocument($data=NULL){
        $pdir = $this->getProjectMetaDataQuery()->getProjectTypeDir()."metadata/plantilles/";
        $scdir = scandir($pdir);
        foreach($scdir as $file){
            if ($file !== '.' && $file !== '..' && substr($file, -4) === ".txt") {
                $plantilla = file_get_contents($pdir.$file);
                $name = substr($file, 0, -4);
                $this->dokuPageModel->setData([PageKeys::KEY_ID => $this->id.":".$name,
                                               PageKeys::KEY_WIKITEXT => $plantilla,
                                               PageKeys::KEY_SUM => "generate project"]);
            }
        }
    }

}
