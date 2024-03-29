<?php
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC . "lib/lib_ioc/");
require_once DOKU_LIB_IOC . "wikiiocmodel/ResourceLocker.php";

//class BasicGetProjectAction extends BasicViewProjectAction implements ResourceLockerInterface {
class BasicGetProjectAction extends ViewProjectAction implements ResourceLockerInterface {

    private $messageLock;
    private $lockStruct;
    private $model;
    private $dokuPageModel;

    protected function setParams($params) {
        parent::setParams($params);
        $this->model = $this->getModel();
        $this->dokuPageModel = $this->model->getDokuPageModel();
        $this->model->setIsOnView(false);
    }

    protected function runAction() {
        if ($this->params[ProjectKeys::KEY_RECOVER_LOCAL_DRAFT]) {
            $response = $this->_getLocalDraftResponse();
            //enviar el contingut actual i determinar si hi ha canvis a l'esborrany
            $response['content'] = $this->_getRawData();
        }elseif ($this->_lockState() === self::LOCKED_BEFORE) {
            // L'usuari té obert el document en una altra sessió
            $rawData = $this->_getRawData();
            $response = $this->_getSelfLockedDialog($rawData);
        }elseif ($this->params[ProjectKeys::KEY_RECOVER_DRAFT]) {
            $response = parent::runAction();
            $this->_getDraftResponse($response);
        }elseif ($this->params[ProjectKeys::KEY_DATE] && $this->params[ProjectKeys::KEY_RECOVER_DRAFT]!==FALSE) {
            $rawData = $this->_getRawData();
            $rawData['draftType'] = $this->_getDraftType($rawData['draftType']);
            $response = $this->_getDraftDialog($rawData);
        }else {
            $response = parent::runAction();
        }

        //Establecimiento del sistema de bloqueo
        if (!$this->params[ProjectKeys::KEY_REV]) {
            $this->lockStruct = $this->requireResource(TRUE);
            $this->messageLock = $this->generateLockInfo($this->lockStruct, $this->params[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
        }
        if ($this->_lockState()) {
            $response['lockInfo'] = $this->lockStruct['info']['locker'];
            $response['lockInfo']['state'] = $this->_lockState();
        }
        return $response;
    }

    protected function postAction(&$response) {
        if ($response) {
            if ($this->messageLock) {
                $response['info'] = self::addInfoToInfo($response['info'], $this->messageLock);
            }else {
                $new_message = $this->generateMessageInfoForSubSetProject($this->params[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET], 'project_edited');
                $response['info'] = self::addInfoToInfo($response['info'], $new_message);
            }
        }
    }

    private function _getRawData() {
        $resp['locked'] = checklock($this->id);
        $resp['content'] = json_encode($this->model->getDataProject());
        $resp['draftType'] = $this->model->hasDraft() ? PageKeys::FULL_DRAFT : PageKeys::NO_DRAFT;
        return $resp;
    }

    private function _getDraftDialog($rawData) {
        $resp = $this->_getLocalDraftDialog($rawData);
        $resp[ProjectKeys::KEY_DRAFT] = $this->model->getDraft();
        $resp['local'] = FALSE;
        $resp[ProjectKeys::KEY_PROJECT_TYPE] = $this->params[ProjectKeys::KEY_PROJECT_TYPE];

        $responseData = $this->model->getData();
        $resp[ProjectKeys::KEY_PROJECT_METADATA] = $responseData[ProjectKeys::KEY_PROJECT_METADATA];
        $resp[ProjectKeys::KEY_PROJECT_VIEWDATA] = $responseData[ProjectKeys::KEY_PROJECT_VIEWDATA];
        return $resp;
    }

    private function _getLocalDraftDialog($rawData) {
        $resp = $this->dokuPageModel->getBaseDataToSend($this->params[PageKeys::KEY_ID], $this->params[PageKeys::KEY_REV]);
        $resp['content'] = $rawData['content'];
        $resp['locked'] = $rawData['locked'];
        $resp['type'] = "project";
        $resp['local'] = TRUE;
        $resp['lastmod'] = WikiIocInfoManager::getInfo('meta')['date']['modified'];
        $resp['show_draft_dialog'] = TRUE;
        return $resp;
    }

    private function _getDraftType($dt=PageKeys::NO_DRAFT) {
        $ret = PageKeys::NO_DRAFT;
        if ($dt !== PageKeys::NO_DRAFT || $this->params[PageKeys::FULL_LAST_LOCAL_DRAFT_TIME]) {
            $fullLastSavedDraftTime = $this->model->getFullDraftDate();
            $fullLastLocalDraftTime = $this->params[PageKeys::FULL_LAST_LOCAL_DRAFT_TIME];

            if ($fullLastLocalDraftTime < $fullLastSavedDraftTime) {
                $ret = PageKeys::FULL_DRAFT;
            }elseif ($fullLastLocalDraftTime > 0) {
                $ret = PageKeys::LOCAL_FULL_DRAFT;
            }
        }
        return $ret;
    }

    private function _getDraftResponse(&$response) {
        if (!$this->model->hasDraft()) {
            throw new DraftNotFoundException($this->params[PageKeys::KEY_ID]);
        }
        if ($this->_lockState() === self::REQUIRED) {
            throw new FileIsLockedException($this->params[PageKeys::KEY_ID]);
        }

        $response['recover_draft'] = TRUE;
        $info = self::generateInfo("warning", WikiIocLangManager::getLang('draft_editing'));
        if (array_key_exists('info', $response)) {
            $info = self::addInfoToInfo($response['info'], $info);
        }
        $response['info'] = $info;
    }

    private function _getLocalDraftResponse() {
        if ($this->_lockState() === self::REQUIRED) {
            throw new FileIsLockedException($this->params[PageKeys::KEY_ID]);
        }
        $resp = $this->_getBaseDataToSend();
        $resp[PageKeys::KEY_RECOVER_LOCAL_DRAFT] = true;
        $info = self::generateInfo('warning', WikiIocLangManager::getLang('local_draft_editing'));
        if (array_key_exists('info', $resp)) {
            $resp['info'] = self::addInfoToInfo($resp['info'], $info);
        }
        return $resp;
    }

    private function _getBaseDataToSend() {
        return $this->dokuPageModel->getBaseDataToSend($this->params[PageKeys::KEY_ID], $this->params[PageKeys::KEY_REV]);
    }

    private function _cleanResponse($text) {
        $pattern = "/^(?:(?!<div class=\"editBox\").)*/s"; //Captura tot el contingut abans del div que contindrá l'editor

        preg_match($pattern, $text, $match);
        $info_m = $match[0];
        $text = preg_replace($pattern, "", $text);

        // Eliminem les etiquetes no desitjades
        $pattern = "/<div id=\"size__ctl\".*?<\/div>\\s*/s";
        $text = preg_replace($pattern, "", $text);

        // Eliminem les etiquetes no desitjades
        $pattern = "/<div class=\"editButtons\".*?<\/div>\\s*/s";
        $text = preg_replace($pattern, "", $text);

        // Copiem el license
        $pattern = "/<div class=\"license\".*?<\/div>\\s*/s";
        preg_match($pattern, $text, $match);
        $license = $match[0];

        // Eliminem l'etiqueta
        $text = preg_replace($pattern, "", $text);

        //eliminem el text de la textarea
        $pattern = "/(<textarea.*?>)(.*?)(<\/textarea>)/s";
        $text = preg_replace($pattern, "$1$3", $text);

        // Copiem el wiki__editbar
        $pattern = "/<div id=\"wiki__editbar\".*?<\/div>\\s*<\/div>\\s*/s";
        preg_match($pattern, $text, $match);
        $meta = $match[0];

        // Eliminem la etiqueta
        $text = preg_replace($pattern, "", $text);

        // Capturem el id del formulari.
        $pattern = "/<form id=\"(.*?)\"/";
        preg_match($pattern, $text, $match);
        $form = $match[1];

        $id = $this->idToRequestId($this->params[PageKeys::KEY_ID]); //igualar al id del formulario

        $pattern = "/<form id=\"" . $form . "\"/";
        $replace = "/<form id=\"form_" . $id . "\"/";
        $text = preg_replace($pattern, $replace, $text);

        // Afegim el id del formulari als inputs
        $pattern = "/<input/";
        $replace = "<input form=\"form_" . $id . "\"";
        $meta = preg_replace($pattern, $replace, $meta);

        // Netegem el valor
        $pattern = "/value=\"string\"/";
        $replace = "value=\"\"";
        $meta = preg_replace($pattern, $replace, $meta);

        //Modifiquem el tamany de la caixa de l'input
        $pattern = "/size=\"50\"/";
        $replace = "style=\"width:99%;\"";
        $meta = preg_replace($pattern, $replace, $meta);

        $response['meta'] = ['id' => "{$id}_metaEditForm",
                             'title' => WikiIocLangManager::getLang('metaEditForm'),
                             'content' => $meta,
                             'type' => "summary"];

        $response['htmlForm'] = $text;

        if ($license) $info = [$license];
        $info[] = preg_replace("/<\/*p>/", "", trim($info_m));
        $responseId = $this->params[PageKeys::KEY_ID] . (($this->params[PageKeys::KEY_REV]) ? PageKeys::REVISION_SUFFIX : "");
        $response['info'] = self::generateInfo('info', $info, $responseId);

        return $response;
    }

    /**
     * Genera un mensaje tipo 'info' como respuesta al tipo de boqueo
     */
    private function generateLockInfo($lockStruct, $id, $subSet) {

        switch ($lockStruct['state']) {
            case self::LOCKED:
                // El fitxer no estava bloquejat
                $infoType = 'info';
                break;

            case self::REQUIRED:
                // S'ha d'afegir una notificació per l'usuari que el te bloquejat
                $message = WikiIocLangManager::getLang('lockedby') . " " . $lockStruct['info']['locker']['name'];
                $infoType = 'error';
                break;

            case self::LOCKED_BEFORE:
                // El teniem bloquejat nosaltres
                $message = WikiIocLangManager::getLang('alreadyLocked');
                $infoType = 'warning';
                break;

            default:
                throw new UnknownTypeParamException($lockStruct['state']);
        }

        if ($message) {
            $message = self::generateInfo($infoType, $message, $id, -1, $subSet);
        }
        return $message;
    }

    private function _lockState() {
        return $this->lockStruct['state'];
    }
    
    /**
     * És el mètode que s'ha d'executar per iniciar el bloqueig.
     * Per defecte el bloqueig es fa només amb les funcions natives de la wiki.
     * @param bool $lock = TRUE produirà bloqueix wikiioc del recurs. El mètode comprova si el recurs està bloquejat i
     * @return array [una constant amb el tipus de bloqueix i un missatge]
     */
    public function requireResource($lock = FALSE) {
        $this->resourceLocker->init($this->params);
        return $this->resourceLocker->requireResource($lock);
    }

}
