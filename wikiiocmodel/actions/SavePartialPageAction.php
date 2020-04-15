<?php
/**
 * Description of SavePartialPageAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

class SavePartialPageAction extends SavePageAction{

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_SAVE;
    }

    protected function startProcess() {
        parent::startProcess();
        $this->dokuPageModel->init($this->params[PageKeys::KEY_ID],
                $this->params[PageKeys::KEY_EDITING_CHUNKS],
                $this->params[PageKeys::KEY_SECTION_ID],
                $this->params[PageKeys::KEY_REV]);
    }

    protected function runProcess() {
        // Si el text conté el '~~USE:WIOCCL~~' no es permet desar
        if (strpos($this->params[PageKeys::KEY_TEXT], '~~USE:WIOCCL~~')) {
            throw new PartialEditNotSupportedException();
        }

        parent::runProcess();
        $this->getModel()->removeChunkDraft($this->params[PageKeys::KEY_SECTION_ID]);
        $this->lockStruct = $this->updateLock();

    }

    protected function responseProcess(){



           $response = array_merge($response =  parent::responseProcess(), $this->getModel()->getData());

            if ($this->params[pageKeys::KEY_CANCEL]) {

                $response['cancel_params'] = [
                    'id' => str_replace(":", "_", $this->params[PageKeys::KEY_ID]),
                    'dataToSend' => ['discard_changes' => true],
                    'event' => 'cancel_partial',
                    'chunk' => $this->params[PageKeys::KEY_SECTION_ID]

                ];
                $response['cancel_params']['event'] = 'cancel_partial';

                if (isset($this->params['keep_draft'])) {
                    $response['cancel_params']['dataToSend']['keep_draft'] = $this->params['keep_draft'];
                }
            }
            // TODO: afegir el 'info' que correspongui
            if (!$response['info']) {
                $response['info'] = self::generateInfo(
                    "info",
                    sprintf(WikiIocLangManager::getLang('section_saved'), $this->params[PageKeys::KEY_SECTION_ID]),
                    $response["structure"]["id"],
                    15
                );
            }

            $this->addMetaTocResponse($response);

            $response['revs'] = $this->getRevisionList();
            $response["lockInfo"] = $this->lockStruct["info"];

            return $response;
    }
}
