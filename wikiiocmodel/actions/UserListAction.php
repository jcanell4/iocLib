<?php
/**
 * Description of UserListAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

class UserListAction  extends AbstractWikiAction {

    const OF_A_PROJECT = "ofAProject";
    const BY_PAGE_PERMSION = "byPagePermision";
    const BY_NAME = "byName";

    public function responseProcess() {
        $paramsArr = $this->params;
        $ret = null;
        switch ($paramsArr[PageKeys::KEY_DO]){
            case self::OF_A_PROJECT:
                //JOSEP: TODO. Falta fer una funció que retorno tots els usuaris d'un projecte
                break;
            case self::BY_PAGE_PERMSION:
                $ret = PagePermissionManager::getListUsersPagePermission($paramsArr[PageKeys::KEY_ID], AUTH_EDIT);
                break;
            case self::BY_NAME:
                $ret = PagePermissionManager::getUserList($paramsArr[PageKeys::KEY_FILTER])['values'];
                break;
            default :
                //error;
                throw new IncorrectParametersException();
        }
        return $ret;
    }

}
