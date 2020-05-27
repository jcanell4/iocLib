<?php
/**
 * Description of SavePageAction
 * @author Xavier García <xaviergaro.dev@gmail.com>
 */
if (!defined('DOKU_INC')) die();

class SavePageAction extends RawPageAction {

    protected $deleted = FALSE;
    protected $subAction;
    private $code = 0;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_SAVE;
    }

    protected function startProcess(){
        $this->subAction = $this->params[PageKeys::KEY_DO];
        parent::startProcess();

        // ALERTA[Xavi] Alguns dels params passats no es troben al $this->params
        if (isset($_REQUEST['keep_draft'])) {
            $this->params['keep_draft'] = $_REQUEST['keep_draft']==="true";
        }
    }

    protected function runProcess(){
        global $ACT;

        if ($this->params[PageKeys::KEY_DO]===PageKeys::DW_ACT_SAVE && !WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_EXISTS)) {
            throw new PageNotFoundException($this->params[PageKeys::KEY_ID]);
        }

        $ACT = act_permcheck($ACT);
        if ($ACT === "denied"){
            throw new InsufficientPermissionToCreatePageException($this->params[PageKeys::KEY_ID]);
        }

        if ($this->checklock() === LockDataQuery::LOCKED) {
            throw new FileIsLockedException($this->params[PageKeys::KEY_ID]);
        }

        $this->lockStruct = $this->updateLock();
        if ($this->lockState() === self::LOCKED) {
            $this->_save();
            if ($this->subAction === PageKeys::DW_ACT_SAVE_REV || $this->deleted) {
                $this->leaveResource(TRUE);
            }
        }
    }

    protected function responseProcess() {
        global $TEXT, $ID;

        $suffix = $this->params[PageKeys::KEY_REV] ? PageAction::REVISION_SUFFIX : '';
        $response['code'] = $this->code;

        if ($this->deleted) {
            $response['deleted'] = TRUE;
            $type = 'success';
            $message = sprintf(WikiIocLangManager::getLang('deleted'), $this->params[PageKeys::KEY_ID]);
            $duration = NULL;
        }
        else {
            $message = WikiIocLangManager::getLang('saved');

            if ($this->params[PageKeys::KEY_CANCEL_ALL] || $this->params[PageKeys::KEY_CANCEL]) {

                $response['code'] = "cancel_document";
                $response['cancel_params']['id'] = str_replace(":", "_", $this->params[PageKeys::KEY_ID]);
                $response['cancel_params']['dataToSend'] = ['discardChanges' => true];
                $response['cancel_params']['event'] = 'cancel';

                if ($this->params['close']) {
                    $response['cancel_params']['dataToSend']['close'] = $this->params['close'];
                    $response['cancel_params']['dataToSend']['no_response'] = true;
                }

                if (isset($this->params['keep_draft'])) {
                    $response['cancel_params']['dataToSend']['keep_draft'] = $this->params['keep_draft'];
                }

            } elseif ($this->params[PageKeys::KEY_REV]) {
                $message = WikiIocLangManager::getLang('reverted');

                if ($this->params[PageKeys::KEY_RELOAD]) {
                    $response['reload']['id'] = $ID;
                    $response['reload']['call'] = 'edit';
                } else {
                    $response['reload']['id'] = $ID;
                    $response['reload']['call'] = 'page';
                }
            } else {
                $response['formId'] = 'form_' . WikiPageSystemManager::getContainerIdFromPageId($ID) . $suffix;
                $response['inputs'] = ['date' => WikiIocInfoManager::getInfo("meta")['date']['modified'],
                                       PageKeys::CHANGE_CHECK => md5($TEXT)
                                      ];
            }

            $type = 'success';
            $duration = 15;
        }

        $response['lockInfo'] = $this->lockStruct['info'];
        $response['id'] = WikiPageSystemManager::getContainerIdFromPageId($this->params[PageKeys::KEY_ID]);
        $response['info'] = self::generateInfo($type, $message, $response[PageKeys::KEY_ID], $duration);

        if ($this->params[PageKeys::KEY_REV]) {
            //Codi per tancar la pestanya de la revisió
            $response['close']['id'] = $response[PageKeys::KEY_ID].$suffix;
            $response['close']['idToShow'] = $response[PageKeys::KEY_ID];
        }

        return $response;
    }

    private function _save(){
        //spam check
        if (checkwordblock()) {
            throw new WordBlockedException();
        }
        //conflict check
        if ($this->subAction !== PageKeys::DW_ACT_SAVE_REV // ALERTA[Xavi] els revert ignoren la data del document
            && $this->params[PageKeys::KEY_DATE] != 0
            && WikiIocInfoManager::getInfo("meta")["date"]["modified"] > $this->params[PageKeys::KEY_DATE] ){
            //return 'conflict';
            throw new DateConflictSavingException();
        }

        //save it
        //saveWikiText($ID,con($PRE,$TEXT,$SUF,1),$SUM,$INPUT->bool('minor')); //use pretty mode for con
        $toSave = con($this->params[PageKeys::KEY_PRE],
                      $this->params[PageKeys::KEY_WIKITEXT],
                      $this->params[PageKeys::KEY_SUF], 1);
        if (strtoupper($this->params["contentFormat"]) === self::HTML_FORMAT && strtoupper($this->dokuPageModel->format) !== 'HTML'){
            $toSave = $this->translateToDW($toSave);
        }
        $this->dokuPageModel->setData(array(
                                        PageKeys::KEY_WIKITEXT => $toSave,
                                        PageKeys::KEY_SUM      => $this->params[PageKeys::KEY_SUM],
                                        PageKeys::KEY_MINOR    => $this->params[PageKeys::KEY_MINOR])
                                     );

        //delete draft
        $this->dokuPageModel->removeFullDraft();

        // Eliminem el fitxer d'esborranys parcials. ALERTA[Xavi] aquesta comprovació no s'hauria de fer! s'ha de mirar com restructurar el SavePartialPageAction perquè no es faci aquesta crida
        if (!isset($this->params[PageKeys::KEY_SECTION_ID])){ // TODO[Xavi] Fix temporal
            $this->getModel()->removePartialDraft();
        }

        // Si s'ha eliminat el contingut de la pàgina, ho indiquem a l'atribut $deleted i desbloquegem la pàgina
        $this->deleted = $this->isEmptyText($this->params);
    }

    private function isEmptyText($param) {
        $text = trim($param[PageKeys::KEY_PRE].
                     $param[PageKeys::KEY_WIKITEXT].
                     $param[PageKeys::KEY_SUF]
                    );
        return ($text == NULL);
    }
}
