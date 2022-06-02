<?php
if (!defined('DOKU_INC')) die();

class BasicViewUpdatableProjectAction extends BasicViewProjectAction{

    const FORA_FINESTRA = 0;
    const IS_UPDATED = 1;
    const NO_IS_UPDATED = 2;

    protected function runAction() {
        $response = parent::runAction();

        $projectModel = $this->getModel();
        
        if ($projectModel->isProjectGenerated()) {
            $estat = $this->isUpdatedDate($this->params[ProjectKeys::KEY_METADATA_SUBSET]);
            $response[AjaxKeys::KEY_ACTIVA_UPDATE_BTN] = ($estat === self::NO_IS_UPDATED) ? "1" : "0";
        }
        $response[AjaxKeys::KEY_ACTIVA_FTP_PROJECT_BTN] = $projectModel->haveFilesToExportList();

        return $response;
    }

    protected function isUpdatedDate($metaDataSubSet) {
        return self::stIsUpdatedDate($this, $metaDataSubSet);
    }

    public static function stIsUpdatedDate($obj, $metaDataSubSet) {
        $isUpdated = self::FORA_FINESTRA;
        $projectModel = $obj->getModel();
        
        $updatedDate = $projectModel->getProjectSystemSubSetAttr("updatedDate", $metaDataSubSet, 0);

//        if ($projectModel->getProjectSystemSubSetAttr("updatedDate", $metaDataSubSet) !== NULL) {
        if ($updatedDate !== NULL) {

            $confProjectType = $obj->modelManager->getConfigProjectType();
            //obtenir la ruta de la configuració per a aquest tipus de projecte
            $projectTypeConfigFile = $projectModel->getProjectTypeConfigFile();

            $cfgProjectModel = $confProjectType."ProjectModel";
            $configProjectModel = new $cfgProjectModel($obj->persistenceEngine);

            $configProjectModel->init([ProjectKeys::KEY_ID              => $projectTypeConfigFile,
                                       ProjectKeys::KEY_PROJECT_TYPE    => $confProjectType,
                                       ProjectKeys::KEY_METADATA_SUBSET => $metaDataSubSet
                                    ]);
            //Obtenir les dades de la configuració per a aquest tipus de projecte
            $metaDataConfigProject = $configProjectModel->getCurrentDataProject($metaDataSubSet);

            if ($metaDataConfigProject['arraytaula']) {
                $arraytaula = is_string($metaDataConfigProject['arraytaula'])?json_decode($metaDataConfigProject['arraytaula'], TRUE):$metaDataConfigProject['arraytaula'];
                $anyActual = date("Y");
                $dataActual = new DateTime();
                $dataActual->setTime(0, 0, 0);

                foreach ($arraytaula as $elem) {
                    if ($elem['key']==="inici_semestre_1") {
                        $inici_semestre1 = self::stObtenirData($elem['value'], $anyActual);
                    }else if ($elem['key']==="fi_semestre_1") {
                        $fi_semestre1 = self::stObtenirData($elem['value'], $anyActual);
                    }
                    if ($elem['key']==="inici_semestre_2") {
                        $inici_semestre2 = self::stObtenirData($elem['value'], $anyActual);
                    }else if ($elem['key']==="fi_semestre_2") {
                        $fi_semestre2 = self::stObtenirData($elem['value'], $anyActual);
                    }
                }
                if ($inici_semestre1 > $fi_semestre1) {
                    $inici_semestre1 = date_sub($inici_semestre1, new DateInterval('P1Y'));
                }
                if ($inici_semestre2 > $fi_semestre2) {
                    $inici_semestre2 = date_sub($inici_semestre2, new DateInterval('P1Y'));
                }
                $finestraOberta = $dataActual >= $inici_semestre1 && $dataActual <= $fi_semestre1;
                if ($finestraOberta){
                    $inici_semestre = $inici_semestre1;
                    $fi_semestre = $fi_semestre1;
                }else{
                    $finestraOberta = $dataActual >= $inici_semestre2 && $dataActual <= $fi_semestre2;
                    if ($finestraOberta){
                        $inici_semestre = $inici_semestre2;
                        $fi_semestre = $fi_semestre2;
                    }
                }

                if ($finestraOberta) {
//                    $updatedDate = $projectModel->getProjectSystemSubSetAttr("updatedDate");
                    $isUpdated = ($updatedDate && $updatedDate >= $inici_semestre->getTimestamp()) ? self::IS_UPDATED : self::NO_IS_UPDATED;
                }
            }
        }
        return $isUpdated;
    }

    /**
     * Retorna una data UNIX a partir de:
     * @param string $diames en format "01/06"
     * @param string $anyActual
     * @return object DateTime
     */
    public static function stObtenirData($diames, $anyActual) {
        $mesdia = explode("/", $diames);
        return date_create($anyActual."/".$mesdia[1]."/".$mesdia[0]);
    }

}
