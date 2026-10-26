<?php
/**
 * EditPageAction: procedimientos comunes en la jerarquía de edición de páginas
 * @culpable Rafa
 */
if (!defined("DOKU_INC")) die();
require_once(DOKU_INC . "inc/actions.php");

abstract class EditPageAction extends PageAction {

    protected function generateLockInfo($lockState, $id, $structured=FALSE, $section=NULL) {
        $message = null;

        switch ($lockState) {
            case self::LOCKED:
                // El fitxer no estava bloquejat
                if ($structured) {
                    $message = WikiIocLangManager::getLang('chunk_editing'). $id . ':' . $section;
                    $infoType = 'info';
                }
                break;

            case self::REQUIRED:
                // S'ha d'afegir una notificació per l'usuari que el te bloquejat
                $lockingUser = WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_LOCKED);
                $message = WikiIocLangManager::getLang('lockedby') . ' ' . $lockingUser;
                $infoType = 'error';
                break;

            case self::LOCKED_BEFORE:
                // El teniem bloquejat nosaltres
                $message = WikiIocLangManager::getLang('alreadyLocked');
                $infoType = 'warning';
                break;

            default:
                throw new UnknownTypeParamException($lockState);
        }

        if ($message) {
            $message = self::generateInfo($infoType, $message, $id);
        }
        return $message;
    }

}
