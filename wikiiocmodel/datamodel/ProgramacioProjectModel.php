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

        $projectesPlaTreball = ["ptfct", "ptfploe", "ptfplogse", "sintesi"];
        $old_dir = explode(":", $ns);
        $old_name = array_pop($old_dir);
        $old_dir = implode(":", $old_dir);
        $this->projectMetaDataQuery->changeNsProgramacioField("$old_dir:$old_name", "$old_dir:$new_name", $projectesPlaTreball);

        $this->postRenameProject($ns, $new_name);
    }

}

