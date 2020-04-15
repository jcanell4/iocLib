<?php
if (!defined('DOKU_INC')) die();

class CreateSubprojectMetaDataAction extends ProjectMetadataAction {

    /**
     * Crea una estructura de directorios para el nuevo proyecto (tipo de proyecto)
     * a partir del archivo de configuración configMain.json
     */
    public function responseProcess() {
        $parent_id = $this->params['parent_id'];
        $parent_projectType = $this->params['parent_projectType'];
        $new_id = $this->params[ProjectKeys::KEY_ID];
        $new_projectType = $this->params[ProjectKeys::KEY_PROJECT_TYPE];

        $projectModel = $this->getModel();
        $projectModel->init([ProjectKeys::KEY_ID              => $parent_id,
                             ProjectKeys::KEY_PROJECT_TYPE    => $parent_projectType,
                             ProjectKeys::KEY_METADATA_SUBSET => $this->params[ProjectKeys::KEY_METADATA_SUBSET]
                           ]);

        //Verifica que el proyecto solicitado sea un proyecto existente
        $listProjectTypes = $projectModel->getListProjectTypes();
        if (!in_array($new_projectType, $listProjectTypes)) {
            throw new UnknownProjectException($new_id, "El tipus de projecte so·licitat no està permés.");
        }
        //No se permite la creación de un nuevo proyecto dentro de un proyecto hijo
        $hasProject = $projectModel->getThisProject($new_id);
        if ($hasProject['nsproject'] !== $parent_id) {
            throw new UnknownProjectException($new_id, "No es permet la creació d'un projecte dins d'un subprojecte.");
        }

        $action = $this->getModelManager()->getActionInstance("CreateProjectMetaDataAction");
        $ret = $action->get($this->params);

        return $ret;
    }
}