<?php
/**
 * Description of RenderedPageAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

abstract class RenderedPageAction extends PageAction{

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->setRenderer(TRUE);   //Indica que la resposta es renderitza i caldrà llençar l'esdeveniment quan calgui
    }

    protected function responseProcess(){
        return self::staticResponseProcess($this);
    }

    static function staticResponseProcess($obj){
        $response = $obj->getModel()->getData();

        $obj->addMetaTocResponse($response);
        $response[PageKeys::KEY_REVISIONS] = $obj->getRevisionList();
        $obj->addNotificationsMetaToResponse($response);

        $meta = WikiIocInfoManager::getInfo('meta');
        $response['structure']['partialDisabled'] = isset($meta['partialDisabled']) ? $meta['partialDisabled'] : FALSE;

        return $response;
    }
}
