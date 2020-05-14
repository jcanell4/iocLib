<?php
/**
 * AdminAuthorization: Extensión clase Autorización para los comandos
 * que precisan una autorización mínima de AUTH_ADMIN
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
require_once (DOKU_INC . "inc/auth.php");

/*
 * NOTA DE JOSEP: Aquesta classe i la defaultProject haurien de se la mateixa. L'admin i el manager de de projecte o de no projecte són els mateixos!
 */

class AdminAuthorization extends ProjectCommandAuthorization {

    public function canRun($permis=AUTH_ADMIN, $type_exception=NULL) {
//        if ( parent::canRun() && $this->permission->getInfoPerm() < AUTH_ADMIN) {
//            $this->errorAuth[self::ERROR_KEY] = TRUE;
//            $this->errorAuth[self::EXCEPTION_KEY] = 'AuthorizationNotCommandAllowed';
//            $this->errorAuth[self::EXTRA_PARAM_KEY] = $this->permission->getIdPage();
//        }
        parent::canRun($permis, $type_exception);
        return !$this->errorAuth[self::ERROR_KEY];
    }

}
