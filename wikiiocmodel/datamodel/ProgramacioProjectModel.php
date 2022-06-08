<?php
/**
 * ProgramacioProjectModel: Métodes comuns als projectes de tipus Programació
 * @culpable Rafael <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class ProgramacioProjectModel extends UniqueContentFileProjectModel {

    /**
     * @overwrite
     * Canvia el nom dels directoris del projecte indicat, ...
     * Canvia, si s'escau, el valor del camp nsProgramacio als projectes del tipus "Pla de Treball"
     * @param string $ns : ns original del projecte
     * @param string $new_name : nou nom pel projecte
     * @param string $persons : noms dels autors i els responsables separats per ","
     */
    public function renameProject($ns, $new_name, $persons) {
        $this->preRenameProject($ns, $new_name, $persons);

        $projectTypes = ["ptfct", "ptfploe", "ptfplogse", "sintesi"];
        $old_dir = explode(":", $ns);
        $old_name = array_pop($old_dir);
        $old_dir = implode(":", $old_dir);
        $field = "nsProgramacio";
        /**
         * Informa si en les dades del projecte el camp 'field' conté el valor 'value'
         * @param array $dades : array de dades del projecte
         * @param array $params : ['field', 'value']
         * @return boolean
         */
        $function = function($dades, $params) {
                        $field = $params[0];
                        $value = $params[1];
                        return (is_array($dades) && !empty($dades[$field]) && $dades[$field] === $value);
                    };
        $callback = ['function' => $function,
                     'params' => [$field, "$old_dir:$old_name"]
                    ];
        $projectList = $this->projectMetaDataQuery->selectProjectsByField($callback, $projectTypes);
        if (!empty($projectList)) {
            $summary = "$field: canvi de nom de la programació associada";
            $this->projectMetaDataQuery->changeFieldValueInProjects($field, "$old_dir:$new_name", $projectList, $summary, $callback);
        }

        $this->postRenameProject($ns, $new_name);
    }
    
    /**
     * Elimina els directoris del projecte indicat i les seves referències i enllaços
     * @param string $ns : ns del projecte
     * @param string $persons : noms dels autors i els responsables separats per ","
     */
    public function removeProject($ns, $persons) {
        parent::removeProject($ns, $persons);
        
        //4. Elimina les referències externes a aquest projecte (nsProgramacio) en els plans de treball
        $projectTypes = ["ptfct", "ptfploe", "ptfplogse", "sintesi"];
        $field = "nsProgramacio";
        /**
         * Informa si en les dades del projecte el camp 'field' conté el valor 'value'
         * @param array $dades : array de dades del projecte
         * @param array $params : ['field', 'value']
         * @return boolean
         */
        $function = function($dades, $params) {
                        $field = $params[0];
                        $value = $params[1];
                        return (is_array($dades) && !empty($dades[$field]) && $dades[$field] === $value);
                    };
        $callback = ['function' => $function,
                     'params' => [$field, $ns]
                    ];
        $projectList = $this->projectMetaDataQuery->selectProjectsByField($callback, $projectTypes);
        if (!empty($projectList)) {
            $summary = "$field: la programació $ns associada ha estat eliminada";
            $this->projectMetaDataQuery->changeFieldValueInProjects($field, "", $projectList, $summary, $callback);
        }
    }

    public function canDocumentBeEdited($documentId){
        $data = $this->getDataProject($this->getId(), $this->getProjectType(), "management");
        return $data['workflow']['currentState'] && ($data['workflow']['currentState']=="creating" || $data['workflow']['currentState']=="modifiying");
    }

    public function stateProcess($id, $metaDataQuery, $newState, $remarks, $subSet, $user=FALSE) {
        $actionCommand = $this->getModelAttributes(AjaxKeys::KEY_ACTION);
        $metaDataManagement = $metaDataQuery->getDataProject($id);
        $currentState = $metaDataManagement['workflow']['currentState'];

        if ($currentState !== $newState) {
            $newMetaData['changeDate'] = date("Y-m-d");
            $newMetaData['oldState'] = $currentState;
            $newMetaData['newState'] = $newState;
            $newMetaData['changeAction'] = $actionCommand;
            $newMetaData['user'] = ($user) ? $user : WikiIocInfoManager::getInfo("userinfo")['name'];
            $newMetaData['remarks'] = $remarks;

            $metaDataManagement['workflow']['stateHistory'][] = $newMetaData;
            $metaDataManagement['workflow']['currentState'] = $newState;

            $metaDataQuery->setMeta(json_encode($metaDataManagement), $subSet, "canvi d'estat", NULL);
        }
        return $currentState;
    }

    public function getCurrentWorkflowActionAttributes($currentState, $actionCommand){
        $workflowJson = $this->getMetaDataJsonFile(FALSE, "workflow.json", $currentState);
        if(isset($workflowJson['actions'][$actionCommand]["shortcut"])){
            $workflowJson = $this->getMetaDataJsonFile(FALSE, "workflow.json", $workflowJson['actions'][$actionCommand]["shortcut"]);
        }
        return $workflowJson['actions'][$actionCommand];
    }

    public function getCurrentState($subSet="management") {
        $id = $this->getId();
        $projectType = $this->getProjectType();
        $metaDataQuery = $this->getPersistenceEngine()->createProjectMetaDataQuery($id, $subSet, $projectType);
        $currentState = $metaDataQuery->getDataProject($id)['workflow']['currentState'];
        return $currentState;
    }

}

