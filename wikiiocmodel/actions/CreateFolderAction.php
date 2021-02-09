<?php
/**
 * CreateFolderAction: crea una nueva carpeta para el proyecto en la ruta especificada
 * @culpable Rafael
 */
if (!defined('DOKU_INC')) die();

class CreateFolderAction extends ProjectAction {

    protected function responseProcess() {
        $id = $this->params[ProjectKeys::KEY_ID];
        $projectId = $this->params[ProjectKeys::KEY_PROJECT_ID];
        $projectModel = $this->getModel();

        $projectModel->init([ProjectKeys::KEY_ID              => $projectId,
                             ProjectKeys::KEY_PROJECT_TYPE    => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                             ProjectKeys::KEY_METADATA_SUBSET => $this->params[ProjectKeys::KEY_METADATA_SUBSET]
                           ]);

        //sólo se ejecuta si existe el proyecto
        if ($projectModel->existProject()) {

            if ($projectModel->folderExists($id)) {
                throw new PageAlreadyExistsException($id, 'pageExists');
            }
            //No se permite la creación de una carpeta dentro de un proyecto hijo
            $hasProject = $projectModel->getThisProject($id);
            if ($hasProject['nsproject'] !== $projectId) {
                throw new UnknownProjectException($id, "No es permet la creació d'una carpeta dins d'un subprojecte.");
            }

            if ($this->projectModel->createFolder($id)) {
                $response['info'] = self::generateInfo("info", WikiIocLangManager::getLang('folder_created')." ($id)", $projectId);
            }else {
                $response['info'] = self::generateInfo("error", WikiIocLangManager::getLang('folder_created_error')." ($id)", $projectId);
                $response['alert'] = WikiIocLangManager::getLang('folder_created_error')." ($id)";
            }
        }
        return $response;
    }

}
