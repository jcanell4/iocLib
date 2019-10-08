<?php
if (!defined("DOKU_INC")) die();

class ResourceLocker implements ResourceLockerInterface, ResourceUnlockerInterface {
    //RETORNAT PER checklock
    public static $ST_UNLOCKED = ResourceLockerInterface::LOCKED; // El recurs no es troba bloquejat per ningú
    public static $ST_LOCKED = ResourceLockerInterface::REQUIRED; // // El recurs es troba bloquejat per un altre usuari
    public static $ST_LOCKED_BEFORE = ResourceLockerInterface::LOCKED_BEFORE; // El recurs està bloquejat pel mateix usuari des d'un altre ordinador

    //RETORNAT PER requireResource o leaveResource
    public static $RS_LOCKED = ResourceLockerInterface::LOCKED; // El recurs no es trobava bloquejat per ningú I s'ha aconseguit bloquejar
    public static $RS_REQUIRED = ResourceLockerInterface::REQUIRED; // El recurs es troba bloquejat per un altre usuari i es guarda una petició
    public static $RS_LOCKED_BEFORE = ResourceLockerInterface::LOCKED_BEFORE; // El recurs està bloquejat pel mateix usuari des d'un altre ordinador

    protected $lockDataQuery;
    protected $params;
    protected $metaDataSubSet;
    protected $lockProjectDir = FALSE; //indica si hay que bloquear el directorio de proyecto

    public function __construct(BasicPersistenceEngine $persistenceEngine, $params=NULL) {
        $this->lockDataQuery = $persistenceEngine->createLockDataQuery();
        $this->params = $params;
    }

    public function init($params, $lockProjectDir=FALSE){
        $this->params = $params;
        $this->lockProjectDir = $lockProjectDir;
        $this->metaDataSubSet = ($params[ProjectKeys::KEY_METADATA_SUBSET]) ? "-".$params[ProjectKeys::KEY_METADATA_SUBSET] : "";
    }

    /**
     * Es tracta del mètode que hauran d'executar en iniciar el bloqueig. Per  defecte no bloqueja el recurs, perquè
     * actualment el bloqueig es realitza internament a les funcions natives de la wiki. Malgrat tot, per a futurs
     * projectes es contempla la possibilitat de fer el bloqueig directament aquí, si es passa el paràmetre amb valor
     * TRUE. EL mètode comprova si algú està bloquejant ja el recurs i en funció d'això, retorna una constant amb el
     * resultat obtingut de la petició.
     * @param bool $lock
     * @return [state:int, info:string]
     */
    public function requireResource($lock=FALSE) {
        $state = $this->_requireResource($lock, $this->params[PageKeys::KEY_REFRESH]);
        if ($this->lockProjectDir && $state["state"]===self::LOCKED)
            $state = $this->_requireResource($lock, $this->params[PageKeys::KEY_REFRESH], $this->lockProjectDir);

        return $state;
    }

    private function _requireResource($lock=FALSE, $refresh=FALSE, $lockProjectDir=FALSE) {
        $state = array();
        if ($lockProjectDir)
            $docId = $this->params[PageKeys::KEY_ID];
        else
            $docId = $this->params[PageKeys::KEY_ID].$this->metaDataSubSet;

        $lockState = $this->lockDataQuery->checklock($docId);

        switch ($lockState) {
            case LockDataQuery::LOCKED:
                $state["state"] = self::REQUIRED;
                if($this->params[PageKeys::KEY_TO_REQUIRE]){
                    $state["info"] = $this->lockDataQuery->addRequirement($docId);
                }else{
                    $state["info"] = $this->lockDataQuery->getLockInfo($docId);
                }
                break;

            case LockDataQuery::UNLOCKED:
                $state["state"] = self::LOCKED;
                $state["info"] = $this->lockDataQuery->xLock($docId, $lock, $refresh);
                break;

            case LockDataQuery::LOCKED_BEFORE:
                $state["state"] = self::LOCKED_BEFORE;
                if($this->params[PageKeys::KEY_FORCE_REQUIRE]){
                    $state["info"] = $this->lockDataQuery->xLock($docId, TRUE, TRUE);
                }else{
                    $state["info"] = $this->lockDataQuery->xLock($docId, FALSE, TRUE);
                }
                break;

            default:
                throw new UnexpectedLockCodeException($lockState); // TODO[Xavi] Canviar per excepció més apropiada i localitzada
        }

        return $state;
    }

    public function leaveResource($unlock=FALSE) {
        $returnState = $this->_leaveResource($unlock, FALSE);
        if ($this->lockProjectDir)
            $returnState = $this->_leaveResource($unlock, $this->lockProjectDir);

        return $returnState;
    }

    /**
     * Es tracta del mètode que hauran d'executar en iniciar el desbloqueig o també quan l'usuari cancel·la la demanda
     * de bloqueig. Per  defecte no es desbloqueja el recurs, perquè actualment el desbloqueig es realitza internament
     * a les funcions natives de la wiki. Malgrat tot, per a futurs projectes es contempla la possibilitat de fer el
     * desbloqueig directament aquí, si es passa el paràmetre amb valor TRUE. EL mètode retorna una constant amb el
     * resultat obtingut de la petició.
     * @param bool $unlock
     * @param bool $lockProjectDir : indica si el que s'ha de bloquejar és el directori de projecte
     * @return int
     */
    private function _leaveResource($unlock=FALSE, $lockProjectDir=FALSE) {
        if ($lockProjectDir)
            $docId = $this->params[PageKeys::KEY_ID];
        else
            $docId = $this->params[PageKeys::KEY_ID].$this->metaDataSubSet;

        $lockState = $this->lockDataQuery->checklock($docId, TRUE);

        switch ($lockState) {
            case LockDataQuery::LOCAL_LOCKED_BEFORE:
                // Bloquejat per aquest usuari
                $this->lockDataQuery->xUnlock($docId, $unlock);

            case LockDataQuery::LOCKED_BEFORE:
                $returnState = self::UNLOCKED;
                break;

            case LockDataQuery::LOCKED:
                // Bloquejat per altre usuari
                $this->lockDataQuery->removeRequirement($docId);
                $returnState = self::LEAVED;
                break;

            case LockDataQuery::UNLOCKED:
            default:
                // Estava desbloquejat: No cal fer res
                $returnState = self::OTHER;
                break;
        }

        return $returnState; // TODO[Xavi] Retorna el codi correcte
    }

    public function checklock() {
        if ($this->lockProjectDir)
            $returnState = $this->lockDataQuery->checklock($this->params[PageKeys::KEY_ID]);
        else
            $returnState = $this->lockDataQuery->checklock($this->params[PageKeys::KEY_ID].$this->metaDataSubSet);

        return $returnState;
    }

    public function isLockedChild($id) {
        return $this->lockDataQuery->isLockedChild($id);
    }

    public function updateLock() {
        return $this->_requireResource(TRUE, TRUE);
    }

}