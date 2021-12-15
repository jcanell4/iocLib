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
    
    public function canDocumentBeEdited($documentId){
        $data = $this->getDataProject($this->getId(), $this->getProjectType(), "management");
        return $data['workflow']['currentState'] && ($data['workflow']['currentState']=="creating" || $data['workflow']['currentState']=="modifiying");
    }
}

