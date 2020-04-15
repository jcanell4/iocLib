<?php
/**
 * Description of HtmlRevisionPageAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

class HtmlRevisionPageAction extends HtmlPageAction {

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_SHOW;
    }

    protected function startProcess() {
        parent::startProcess();
    }

    protected function responseProcess() {
        $response = $this->getModel()->getData();

        $revisionInfo = WikiIocLangManager::getXhtml('showrev');

        // ALERTA[Xavi] Canvis per fer servir una pestanya per revisions
        $response['structure']['id'] .= PageAction::REVISION_SUFFIX;
        // ALERTA[Xavi] Fi Canvis

        $response['structure']['html'] = str_replace($revisionInfo, '', $response['structure']['html']);

        // Si no s'ha especificat cap altre missatge mostrem el de carrega
        if (!$response['info']) {
            $response['info'] = self::generateInfo("warning", trim(strip_tags($revisionInfo)), $response['structure']['id']);
        } else {
            $response['info'] = self::addInfoToInfo($response['info'], self::generateInfo("info", trim(strip_tags($revisionInfo)), $response['structure']['id']));
        }

        $this->addMetaTocResponse($response);

        $response['revs'] = $this->getRevisionList();

        $this->addNotificationsMetaToResponse($response, $response['ns']);

        // Corregim els ids de les metas per indicar que és una revisió
        $this->addRevisionSuffixIdToArray($response['meta']);

        return $response;
    }

}
