<?php
/**
 * SendMessageToRolsAction: Envia una notificació i un missatge als destinataris definits pel seu rol al projecte
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class SendMessageToRolsAction extends NotifyAction {

    private $persistenceEngine;
    private $model;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->persistenceEngine = $modelManager->getPersistenceEngine();
        $ownModel = "AdminModel";
        $this->model = new $ownModel($this->persistenceEngine);
    }

    protected function responseProcess() {
        $rols = explode(",", trim($this->params['rols'], ","));
        $llistaDeProjectes = $this->getProjectsList();
        foreach ($llistaDeProjectes as $elem) {
            $users .= $this->model->getUserRol($rols, $elem['id'], $elem['projectType']) . ",";
        }
        $this->params['to'] = trim($users, ",");
        
        $response['notifications'] = [];
        $notifyResponse = $this->notifyMessageToFrom();
        $response['notifications'][] = $notifyResponse['notifications'];
        $response['info'] = $notifyResponse['info'];
        return $response;
    }

    /** Construeix una llista de projectes que compleixen les condicions */
    private function getProjectsList() {
        $parser = $this->parser($this->params['grups']);
        $listProjects = $this->model->selectProjectsByType($parser['listProjectTypes']);

        foreach ($listProjects as $project) {
            $data_main = $this->model->getDataProject($project['id'], $project['projectType'], "main");
            $data_all = $this->model->getAllDataProject($project['id'], $project['projectType']);
            $root = NodeFactory::getNode($parser['grups'], $parser['mainGroup'], $data_main, $data_all);
            if ($root->getValue()) {
                $llista[] = ['id' => $project['id'],
                             'projectType' => $project['projectType']];
            }
        }
        return $llista;
    }

    private function parser($G) {
        $listProjectTypes = [];
        $grups = (is_string($G)) ? json_decode($G, true) : $G;
        $mainGroup = "grup_${grups['main_group']}";
        foreach ($grups as $key => $grup) {
            if (preg_match("/grup_(.*)/", $key, $g)) {
                if ($grup['projecttype']) {
                    $listProjectTypes[] = $grup['projecttype'];
                }
            }else {
                unset($grups[$key]);
            }
        }
        return ['mainGroup'=>$mainGroup, 'grups'=>$grups, 'listProjectTypes'=>$listProjectTypes];
    }

}

