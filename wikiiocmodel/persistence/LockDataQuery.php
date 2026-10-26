<?php
/**
 * Description of LockDataQuery
 * @author Xavier García <xaviergaro.dev@gmail.com>
 */
if (!defined('DOKU_INC')) die();

class LockDataQuery extends DataQuery
{
    const UNLOCKED = 100; // El recurs no es troba bloquejat per ningú
    const LOCKED = 200;  // El recurs es troba bloquejat per un altre usuari
    const LOCKED_BEFORE = 400; // El recurs està bloquejat per l'usuari actual
    const LOCAL_LOCKED_BEFORE = 800; // El recurs està bloquejat per l'usuari actual usant la sessió actual

    protected $notifyModel;

    public function __construct() {
        $type = WikiGlobalConfig::getConf('notifier_type', 'wikiiocmodel');
        $modelManager = new WikiIocModelManager();
        $this->notifyModel = $modelManager->getNotifyModel($type);
    }

    public function getFileName($id, $especParams = NULL)
    {
        $filename = wikiLockFN($id);
        if ($especParams) {
            $filename .= '.' . $especParams;
        }

        return $filename;
    }

    public function getNsTree($currentNode, $sortBy, $onlyDirs=FALSE, $expandProject=FALSE, $hiddenProjects=FALSE, $root=FALSE)
    {
        throw new UnavailableMethodExecutionException("LockDataQuery#getNsTree");
    }

    /**
     * Enregistra en el fitxer de bloquejos del recurs identificat per id, l'usuari actual com bloquejador del recurs.
     * En cas que el paràmetre lock sigui TRUE, també bloqueja el recurs de la forma habitual.
     *
     * @param String $id
     * @param bool $lock
     */
    public function xLock($id, $lock = FALSE, $keepExtended = FALSE)
    {
        if ($lock) {
            $this->lock($id);
        }
        $file = $this->getFileName($id, "extended");
        if(!$keepExtended || !file_exists($file)){
            // Afegim el fitxer extended buit
            $ret = $this->createExtendedFile($id);
            // TODO: Actualitzar el registre estès de bloquejos
        }else{
            $ret = $this->readExtendedFile($id);
        }

        return $ret;
    }

    private function readExtendedFile($id)
    {
        $file = $this->getFileName($id);
        $extendedFile = $this->getFileName($id, "extended");
        $ret = unserialize(io_readFile($extendedFile, FALSE));
        $ret["locker"]["time"] = filemtime($file);

        return $ret;
    }

    private function createExtendedFile($id)
    {

        $locker = $this->_getLockerInfo($id);

        $extended['locker'] = $locker;
        $extended['requirers'] = [];

        io_saveFile($this->getFileName($id, 'extended'), serialize($extended));

        return $extended;
    }

    private function _getLockerInfo($id)
    {
        $filename = $this->getFileName($id);
        list($ip, $session) = explode("\n", io_readFile($filename));

        if(!$session){
            $session = $_COOKIE["DokuWiki"];
        }
        return [
            'user' => $ip,
            'name' => WikiIocInfoManager::getInfo("userinfo")["name"],
            'session' => $session,
            "time" =>  filemtime($filename)
        ];
    }

    public function getLockInfo($id)
    {
        $extended = array();
        $lockFilename = $this->getFileName($id);
        $lockFilenameExtended = $this->getFileName($id, 'extended');
        $locked = $this->clearLockIfNeeded($id);

        if ($locked) {
            if (@file_exists($lockFilenameExtended)) {
                // S'ha d'actualitzar
                $extended = unserialize(io_readFile($lockFilenameExtended, FALSE));
                $extended["locker"]["time"] =  filemtime($lockFilename);

            } else {
                // S'ha de crear un nou, llegim la informació del lock base
                $extended["locker"] = $this->_getLockerInfo($id);
            }

        } else{
            // ALERTA[Xavi] El document no està bloquejat, aquest cas no s'hauria de donar mai

        }

        return $extended;
    }

    /**
     * Elimina de l'entrada del registre estès de bloquejos del recurs identificat per id, de l'usauri bloquejador.
     * A més activa el sistema de notificacions (NotifyAction) escrivint a la pissarra de cada usuari que tenia
     * demanada la petició de bloqueig (cua de peticions) per indicar que s'ha alliberat el bloqueig i que si ho
     * desitja pot tornar a fer la petició.
     *
     * @param String $id
     * @param bool $unlock
     *      ALERTA[Xavi] $unlock = TRUE: elimina el fitxer de bloqueig de la wiki i l'extended.
     *                   $unlock = FALSE: no elimina el fitxer de la wiki, però elimina l'extended
     */
    public function xUnlock($id, $unlock = FALSE)
    {

        $this->notifyRequirers($id);

        if ($unlock) {
            $this->unlock($id);
        }

        $this->removeExtendedFile($id);
    }

    /**
     * Bloqueja el recurs de la forma habitual.
     *
     * @param String $id
     */
    public function lock($id)
    {
        $locker = $this->checklock($id);

        if ($locker !== self::LOCKED) {
            lock($id);
        } else {
            throw new FileIsLockedException();
        }
    }

    /**
     * Desbloqueja el recurs de forma habitual
     *
     * @param String $id
     */
    public function unlock($id)
    {
        unlock($id);
    }

    /**
     * Busca bloqueix en tots els directoris superiors del recurs pel cual es demana informació de bloqueix
     * @param String $id
     * @return int : UNLOCKED, LOCKED, LOCKED_BEFORE
     */
    public function checklock($id, $checkAutoLock=FALSE) {
        $state = 0;
        do {
            $state = max($state, $this->_checklock($id, $checkAutoLock));
            $id = preg_replace('/(.*):.*/', '$1', $id);
        }while (strpos($id,":") !== FALSE);

        return $state;
    }

    /**
     * Indica si el fitxer està lliure o bloquejat. Pot retornar els següents valors: UNLOCKED, LOCKED, LOCKED_BEFORE
     * @return int
     */
    private function _checklock($id, $checkAutoLock=FALSE)
    {
        $lock = $this->getFileName($id);
        $extendedlock = $this->getFileName($id, 'extended');
        $state = self::LOCKED;

        //no lockfile
        if (!@file_exists($lock)) {
            $state = self::UNLOCKED;
        } else {
            //lockfile expired
            if ((time() - filemtime($lock)) > WikiGlobalConfig::getConf('locktime')) {
                @unlink($lock);
                $state = self::UNLOCKED;
            } else {
                // own lock
                list($ip, $session) = explode("\n", io_readFile($lock));
                if(!$session && @file_exists($extendedlock)){
                    $session =  unserialize(io_readFile($extendedlock, FALSE))['locker']['session'];
                }
                if ($ip == $_SERVER['REMOTE_USER'] || $ip == clientIP()) {
                    if(!$checkAutoLock && $session ===  $_COOKIE["DokuWiki"]){
                           $state = self::UNLOCKED;
                    }else if($session ===  $_COOKIE["DokuWiki"]){
                        $state = self::LOCAL_LOCKED_BEFORE;
                    }else{
                        $state = self::LOCKED_BEFORE;
                    }
                }
            }
        }

        return $state;
    }

    /**
     * Averigua si dentro de la ruta indicada hay alguna página bloqueada
     * @param string $id : wikiruta a examinar (normalmente es un directorio de proyecto)
     * @return boolean : TRUE indica que hay alguna página bloqueada
     */
    public function isLockedChild($id) {
        $locked = FALSE;
        $datadir = WikiGlobalConfig::getConf('datadir') . "/" . str_replace(":", "/", $id);
        $scan = @scandir($datadir);
        if ($scan) {
            foreach ($scan as $file) {
                if (substr($file, -1) !== ".") {
                    $f = str_replace(".txt", "", "$id:$file");
                    if (is_dir("$datadir/$file")) {
                        $locked = $this->isLockedChild($f);
                    }else {
                        $locked = $locked || ($this->_checklock($f) > self::UNLOCKED);
                        if ($locked) break;
                    }
                }
            }
        }
        return $locked;
    }

    /**
     * Afegeix la petició de que un usuari desitja bloquejar el recurs al fitxer de bloquejos estès. A més, activa el
     * sistema de notificacions a fi que l'usuari que manté bloquejat el recurs s'assabenti que hi ha un usuari que
     * reclama també el bloqueig.
     *
     * ALERTA[Xavi] Compte! El id ha de contenir els : com a separadors
     *
     * @param String $id
     */
    public function addRequirement($id)
    {

        // Nom del fitxer:
        $lockFilename = $this->getFileName($id);
        $lockFilenameExtended = $this->getFileName($id, 'extended');
        $alreadyNotified = false;

        // TODO[Xavi] Afegit la neteja de locks (com en el checklock de la wiki)
        $locked = $this->clearLockIfNeeded($id);


        if ($locked) {
            $now = new DateTime();
            $requirerUser = $this->getCurrentUser();
            $requirerTimestamp = $now->getTimestamp();

            if (@file_exists($lockFilenameExtended)) {
                // S'ha d'actualitzar
                $extended = unserialize(io_readFile($lockFilenameExtended, FALSE));
                $extended["locker"]["time"] =  filemtime($lockFilename);

                } else {
                // S'ha de crear un nou, llegim la informació del lock base
                $extended = $this->_getLockerInfo($id);
            }

//            if (isset($extended['requirers'][$requirerUser])) {
//                $alreadyNotified = true;
//            }

            $extended['requirers'][$requirerUser] = $requirerTimestamp;

        } else {
            // ALERTA[Xavi] El document no està bloquejat, aquest cas no s'hauria de donar mai
            return array();

        }

        // Afegir la informació al sistema de bloquejos extés
        io_saveFile($lockFilenameExtended, serialize($extended));

        // Afegir una notificació al usuari que el bloqueja si no existia ja (per evitar que s'envii més d'una notificació en cas d'edicions parcials)
//        if (!$alreadyNotified) {
            $this->addRequirementNotification($extended['locker']['user'], $id, count($extended['requirers']));
//        }


        return $extended; // Test, per afegir breakpoint
    }

    private function addRequirementNotification($lockerId, $docId, $requirers)
    {
        $class_ = $this->notifyModel->getConstClass();
        $message = array(
            "text" => sprintf(WikiIocLangManager::getLang('documentRequired'), $requirers, $docId),
            "timestamp" => date( "d-m-Y H:i:s" ),
        );
        $this->notifyModel->notifyTo($message, $lockerId, $class_::TYPE_MESSAGE, str_replace(":", "_",$lockerId.$docId."requirement"), $class_::MAILBOX_RECEIVED);
    }

    /**
     * Retorna cert si s'han eliminat els bloquejos o no existian i fals en cas contrari.
     *
     * ALERTA[Xavi] Com que depenem de la existencia del lock de la wiki, la eliminació de l'extended només es produeix si el primer s'ha d'eliminar
     *
     * @param String $id
     * @return bool
     */
    private function clearLockIfNeeded($id)
    {
        $clear = true;
        $lock = $this->getFileName($id);

        if (!@file_exists($lock)) {
            $clear = false;
        } else if ((time() - filemtime($lock)) > WikiGlobalConfig::getConf('locktime')) {
            @unlink($lock);
            $clear = false;
        } else {
            list($ip, $session) = explode("\n", io_readFile($lock));
            if ($ip == $_SERVER['REMOTE_USER'] || $ip == clientIP() || $session == session_id()) {
                $clear = false; // Està bloquejat pel mateix usuari
            }
        }

        if (!$clear) {
            $this->removeExtendedFile($id);
        }

        return $clear;
    }

    public function removeExtendedFile($id)
    {
        @unlink($this->getFileName($id, 'extended'));
    }

    private function getCurrentUser()
    {
        // TODO[Xavi] Per determinar que s'ha de fer si no existeix el REMOTE_USER
        // ALERTA[Xavi] Si s'obté el nom de l'usuari del info (WikiIocInfoManager::getInfo('userinfo')['name']) pot no coincidir amb el que es guarda al common.php#lock, ja que utilitza el codi següent:
//        if($_SERVER['REMOTE_USER']) {
        $currentUser = $_SERVER['REMOTE_USER'];
//        } else {
//            $currentUser = clientIP()."\n".session_id());
//        }

        return $currentUser;
    }

    public function removeRequirement($id)
    {
        $lockFilenameExtended = $this->getFileName($id, 'extended');

        // Comprovar si existeix l'extended
        if (@file_exists($lockFilenameExtended)) {
            $requirerUser = $this->getCurrentUser();
            $extended = unserialize(io_readFile($lockFilenameExtended, FALSE));
            $extended["locker"]["time"] =  filemtime($lockFilename);

            // Si existeix eliminar el usuari
            if(isset($extended['requirers'][$requirerUser])){
                unset($extended['requirers'][$requirerUser]);
                // Desar el fitxer
                io_saveFile($lockFilenameExtended, serialize($extended));

                if(count($extended['requirers'])==0){
                 //avisar al locker que ja no es demana
                    $this->addUnrequirementNotification($extended['locker']['user'], $id);
                }
            }
        }
    }

    private function notifyRequirers($id)
    {
        $lockFilenameExtended = $this->getFileName($id, 'extended');
        $extended = unserialize(io_readFile($lockFilenameExtended, FALSE));
        $extended["locker"]["time"] =  filemtime($this->getFileName($id));

        foreach ($extended['requirers'] as $user => $timestamp) {
            $this->addUnlockedNotification($user, $id);
        }

    }

    private function addUnlockedNotification($requirerId, $docId)
    {
        $class_ = $this->notifyModel->getConstClass();
        $message = array(
            "text" => sprintf(WikiIocLangManager::getLang('documentUnlocked'), $docId),
            "timestamp" => date( "d-m-Y H:i:s" ),
        );
        $this->notifyModel->notifyTo($message, $requirerId, $class_::TYPE_MESSAGE, str_replace(":", "_", $requirerId.$docId."release"), $class_::MAILBOX_RECEIVED);
    }

    private function addUnrequirementNotification($lockerId, $docId)
    {
        $class_ = $this->notifyModel->getConstClass();
        $message = array(
            "text" => sprintf(WikiIocLangManager::getLang('documentUnrequired'), $docId),
            "timestamp" => date( "d-m-Y H:i:s" ),
        );
        $this->notifyModel->notifyTo($message, $lockerId, $class_::TYPE_MESSAGE, str_replace(":", "_", $lockerId.$docId."requirement"), $class_::MAILBOX_RECEIVED);
    }


}
