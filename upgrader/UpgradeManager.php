<?php
/**
 * UpgradeManager: Gestiona els processos d'actualització de les versions dels projectes
 * @author rafael
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC.'lib/lib_ioc/');
require_once DOKU_LIB_IOC . "wikiiocmodel/ResourceLocker.php";

class UpgradeManager {

    protected $model;
    protected $resourceLocker;
    protected $projectType;
    protected $metaDataSubSet;
    protected $ver_project = 0;  //versió del projecte actual
    protected $ver_config = 0;   //versió definida en el configMain del tipus de projecte

    public function __construct($model, $projectType=NULL, $metaDataSubSet=NULL, $ver_project=0, $ver_config=0, $type="x") {
        $this->model = $model;
        $this->projectType = ($projectType) ? $projectType : $this->model->getProjectType();
        $this->metaDataSubSet = ($metaDataSubSet) ? $metaDataSubSet : $this->model->getMetaDataSubSet();

        $this->resourceLocker = new ResourceLocker($this->model->getPersistenceEngine());
        $this->resourceLocker->init([ProjectKeys::KEY_ID => $this->model->getId()]);

        if ($ver_project) $this->ver_project = $ver_project;
        if ($ver_config) $this->ver_config = $ver_config;
        $this->validaProcessVersion($type);
    }

    public function preProcess($ver_project, $ver_config, $type, $key) {
        $new_ver = $this->process($ver_project, $ver_config, $type, $key);
        $att = ($type === "fields") ? $type : $key;
        $this->model->setProjectSystemSubSetVersion($att, $new_ver, $this->metaDataSubSet);
        if ($new_ver < $ver_config){
            throw new Exception("Error en l'actualització completa de la versió del projecte.");
        }
    }

    protected function process($ver_project=0, $ver_config=0, $type="x", $key=NULL) {
        $ret = $ver_project;
        if ($ver_project) $this->ver_project = $ver_project;
        if ($ver_config) $this->ver_config = $ver_config;
        $this->validaProcessVersion($type);

        $lockStruct = $this->resourceLocker->requireResource(TRUE);

        if ($lockStruct["state"] === ResourceLockerInterface::LOCKED){
            $dir = WikiIocPluginController::getProjectTypeDir($this->projectType) . "upgrader";
            for ($i=$ver_project+1; $i<=$ver_config; $i++) {
                $uclass = "upgrader_$i";
                $udir = "$dir/$uclass.php";
                if (file_exists($udir)) {
                    require_once $udir;
                    $iupgrade = new $uclass($this->model);
                    if ($iupgrade->upgrade($type, $i, $key)) {
                        $ret = $i;
                    }else {
                        break;  //Se ha producido un error en una actualización y, por tanto, se fuerza la finalización del proceso
                    }
                }else{
                    break;
                }
            }
            $this->resourceLocker->leaveResource(TRUE);
        }else {
            if ($key===NULL) $key = $this->model->getProjectDocumentName();
            $id = $this->model->getId().":$key";
            throw new FileIsLockedException($id, "lockedByUpdate");
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
