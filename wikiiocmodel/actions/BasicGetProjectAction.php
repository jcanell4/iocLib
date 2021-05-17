<?php
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC . "lib/lib_ioc/");
require_once DOKU_LIB_IOC . "wikiiocmodel/ResourceLocker.php";

class BasicGetProjectAction extends BasicViewProjectAction implements ResourceLockerInterface {

    private $messageLock;

    protected function setParams($params) {
        $this->setIsOnView(false); //debe ser anterior a la llamada a parent
        parent::setParams($params);
    }

    protected function runAction() {
        //Establecimiento del sistema de bloqueo
        if ( ! $this->params[PageKeys::KEY_REV] ) {
            $lockStruct = $this->requireResource(TRUE);
            $this->messageLock = $this->generateLockInfo($lockStruct, $this->params[ProjectKeys::KEY_ID], $this->params[ProjectKeys::KEY_METADATA_SUBSET]);
        }
        $response = parent::runAction();
        if ($lockStruct['state']) {
            $response['lockInfo'] = $lockStruct['info']['locker'];
            $response['lockInfo']['state'] = $lockStruct['state'];
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