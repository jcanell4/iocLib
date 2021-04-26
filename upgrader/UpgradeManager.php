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

    public function __construct($model, $projectType=NULL, $metaDataSubSet=NULL, $ver_project=0, $ver_config=0, $type="x") {
        $this->model = $model;
        $this->projectType = ($projectType) ? $projectType : $this->model->getProjectType();
        $this->metaDataSubSet = ($metaDataSubSet) ? $metaDataSubSet : $this->model->getMetaDataSubSet();
        if ($ver_project) $this->ver_project = $ver_project;
        if ($ver_config) $this->ver_config = $ver_config;
        $this->validaProcessVersion($type);
    }

    public function preProcess($ver_project, $ver_config, $type, $key) {
        $new_ver = $this->process($ver_project, $ver_config, $type, $key);
        if ($key) {
            $versions_project[$type][$key] = $new_ver;
        }else {
            $versions_project[$type] = $new_ver;
        }
        $this->model->setProjectSystemSubSetAttr("versions", $versions_project, $this->metaDataSubSet);
        if ($new_ver < $ver_config){
            throw new Exception("Error en l'actualització completa de la versió del projecte.");
        }
    }

    protected function process($ver_project=0, $ver_config=0, $type="x", $key=NULL) {
        $ret = $ver_project;
        if ($ver_project) $this->ver_project = $ver_project;
        if ($ver_config) $this->ver_config = $ver_config;
        $this->validaProcessVersion($type);

        $dir = WikiIocPluginController::getProjectTypeDir($this->projectType) . "upgrader";
        for ($i=$ver_project+1; $i<=$ver_config; $i++) {
            $uclass = "upgrader_$i";
            $udir = "$dir/$uclass.php";
            if (file_exists($udir)) {
                require_once $udir;
                $iupgrade = new $uclass($this->model);
                if ($iupgrade->process($type, $i, $key)) {
                    $ret = $i;
                }else {
                    break;  //Se ha producido un error en una actualización y, por tanto, se fuerza la finalización del proceso
                }
            }else{
                break;
            }
        }
        return $ret;
    }

    private function validaProcessVersion($type) {
        if ($this->ver_project > $this->ver_config) {
            throw new Exception ("La versió de tipus '$type' del projecte és major que la versió corresponent definida al tipus de projecte: $this->ver_project > $this->ver_config");
        }
        if ($this->ver_project === $this->ver_config) {
            throw new Exception ("La versió de tipus '$type' del projecte és igual que la versió corresponent definida al tipus de projecte: $this->ver_project > $this->ver_config");
        }
    }

}
