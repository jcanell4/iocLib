<?php
/**
 * UpgradeManager: Gestiona els processos d'actualització de les versions dels projectes
 * @author rafael
 */
if (!defined("DOKU_INC")) die();

class UpgradeManager {

    protected $model;
    protected $projectType;
    protected $metaDataSubSet;
    protected $ver_project = 0;  //versió del projecte actual
    protected $ver_config = 0;   //versió definida en el configMain del tipus de projecte

    public function __construct($model, $projectType=NULL, $metaDataSubSet=NULL, $ver_project=0, $ver_config=0) {
        $this->model = $model;
        $this->projectType = ($projectType) ? $projectType : $this->model->getProjectType();
        $this->metaDataSubSet = ($metaDataSubSet) ? $metaDataSubSet : $this->model->getMetaDataSubSet();
        if ($ver_project) $this->ver_project = $ver_project;
        if ($ver_config) $this->ver_config = $ver_config;
        $this->validaProcessVersion();
    }

    public function process($ver_project=0, $ver_config=0) {
        if ($ver_project) $this->ver_project = $ver_project;
        if ($ver_config) $this->ver_config = $ver_config;
        $this->validaProcessVersion();

        $dir = WikiIocPluginController::getProjectTypeDir($this->projectType) . "upgrader";
        for ($i=$ver_project; $i<=$ver_config; $i++) {
            $udir = "$dir/upgrader_$i";
            if (file_exists($udir)) {
                require_once $udir;
            }
        }
    }

    private function validaProcessVersion() {
        if ($this->ver_project > $this->ver_config) {
            throw new Exception ("La versió del projecte és major que la versió definida al tipus de projecte: $this->ver_project > $this->ver_config");
        }
        if ($this->ver_project === $this->ver_config) {
            throw new Exception ("La versió del projecte és igual que la versió definida al tipus de projecte: $this->ver_project > $this->ver_config");
        }
    }

}
