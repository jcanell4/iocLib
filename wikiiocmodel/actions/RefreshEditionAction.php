<?php
/**
 * Description of RefreshEditionAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

class RefreshEditionAction extends PageAction implements ResourceLockerInterface {
    private $lockStruct;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_LOCK;
    }

    protected function runProcess() {
        global $ACT;

        if (!WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_EXISTS)) {
            throw new PageNotFoundException($this->params[PageKeys::KEY_ID]);
        }

        $ACT = act_permcheck(PageKeys::DW_ACT_EDIT);

        if ($ACT == PageKeys::DW_ACT_DENIED) {
            throw new InsufficientPermissionToEditPageException($this->params[PageKeys::KEY_ID]);
        }

        $this->lockStruct = $this->requireResource(TRUE);
    }

    protected function responseProcess()
    {
        if($this->lockStruct["state"]!== ResourceLockerInterface::LOCKED){
            //[JOSEP] AIXÒ NO HAURIA DE PASSAR MAI!
            throw new FileIsLockedException($this->params[PageKeys::KEY_ID]);
        }

        $response[PageKeys::KEY_CODETYPE] = ProjectKeys::VAL_CODETYPE_OK;
        return $response;
    }

    /**
     * Es tracta del mètode que hauran d'executar en iniciar el bloqueig. Per  defecte no bloqueja el recurs, perquè
     * actualment el bloqueig es realitza internament a les funcions natives de la wiki. Malgrat tot, per a futurs
     * projectes es contempla la possibilitat de fer el bloqueig directament aquí, si es passa el paràmetre amb valor
     * TRUE. EL mètode comprova si algú està bloquejant ja el recurs i en funció d'això, retorna una constant amb el
     * resultat obtingut de la petició.
     *
     * @param bool $lock
     * @return int
     */
    public function requireResource($lock = FALSE)
    {
        return $this->resourceLocker->requireResource($lock);
    }
}
