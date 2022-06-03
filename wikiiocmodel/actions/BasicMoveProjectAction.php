<?php
/**
 * BasicMoveProjectAction: Mou un projecte a un altre ruta (pot canviar el nom)
 * @autor Rafael <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class BasicMoveProjectAction extends BasicDuplicateProjectAction {

    protected function runAction() {
        $response = parent::runAction();
        $model = $this->getModel();
        $persons = $response[ProjectKeys::KEY_PROJECT_METADATA]['autor']['value'].","
                  .$response[ProjectKeys::KEY_PROJECT_METADATA]['responsable']['value'];
        $model->removeProject($response[ProjectKeys::KEY_OLD_NS], $persons);

        return $response;
    }

    protected function postAction(&$response) {
        $this->resourceLocker->leaveResource(TRUE);
        $new_message = $this->generateMessageInfoForSubSetProject($response[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET], WikiIocLangManager::getLang('project_moved','wikiiocmodel'));
        $response['info'] = self::addInfoToInfo($response['info'], $new_message);
    }

}
