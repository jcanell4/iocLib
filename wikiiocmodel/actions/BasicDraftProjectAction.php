<?php
/**
 * DraftProjectAction: Gestiona l'esborrany del formulari de dades d'un projecte mentre s'està modificant
 * @culpable Rafael
 */
if (!defined("DOKU_INC")) die();

class BasicDraftProjectAction extends ProjectAction {

    private $Do;
    private static $infoDuration = 15;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->Do = PageKeys::DW_ACT_PREVIEW;
    }

    protected function startProcess() {
        $this->projectModel->init([ProjectKeys::KEY_ID              => $this->params[ProjectKeys::KEY_ID],
                                   ProjectKeys::KEY_PROJECT_TYPE    => $this->params[ProjectKeys::KEY_PROJECT_TYPE],
                                   ProjectKeys::KEY_REV             => $this->params[ProjectKeys::KEY_REV],
                                   ProjectKeys::KEY_METADATA_SUBSET => $this->params[ProjectKeys::KEY_METADATA_SUBSET]
                                ]);

        $this->resourceLocker->init($this->params);

        if ($this->params[ProjectKeys::KEY_DO]===ProjectKeys::KEY_SAVE || $this->params[ProjectKeys::KEY_DO]===ProjectKeys::KEY_SAVE_PROJECT_DRAFT) {
            $this->Do = PageKeys::DW_ACT_PREVIEW;
        }else if ($this->params[ProjectKeys::KEY_DO]===PageKeys::DW_ACT_REMOVE || $this->params[ProjectKeys::KEY_DO]===ProjectKeys::KEY_REMOVE_PROJECT_DRAFT) {
            $this->Do = PageKeys::DW_ACT_DRAFTDEL;
        }
    }

    protected function runProcess() {
        if ( ! $this->projectModel->existProject() ) {
            throw new PageNotFoundException($this->ProjectKeys[ProjectKeys::KEY_ID]);
        }

        if ($this->resourceLocker->checklock() === LockDataQuery::LOCKED) {
            throw new FileIsLockedException($this->params[ProjectKeys::KEY_ID]);
        }

        $id = $this->idToRequestId($this->params[ProjectKeys::KEY_ID]);
        if ($this->Do === PageKeys::DW_ACT_PREVIEW) {
            //actualiza la información de bloqueo mientras se siguen modificando los datos del formulario del proyecto
            $response["lockInfo"] = $this->resourceLocker->updateLock()["info"];

            $draft = IocCommon::toArrayThroughArrayOrJson($this->params['draft']);
            $draft['date'] = $this->params['date']; //ATENCIÓN: En principio parecen el mismo dato
            $this->getModel()->saveDraft($draft);
            $response[ProjectKeys::KEY_ID] = $id;
            $response['info'] = self::generateInfo("info", "S'ha desat l'esborrany", $id, self::$infoDuration);
        }
        else if ($this->Do === PageKeys::DW_ACT_DRAFTDEL) {
            $this->getModel()->removeDraft();
            $response[ProjectKeys::KEY_ID] = $id;
        }
        else{
            throw new UnexpectedValueException("Unexpected value '".$this->params[ProjectKeys::KEY_DO]."', for parameter 'do'");
        }
        return $response;
    }

    protected function responseProcess() {
        $this->startProcess();
        $ret = $this->runProcess();
        return $ret;
    }
}
