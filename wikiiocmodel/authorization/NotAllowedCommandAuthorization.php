<?php
/**
 * NotAllowedCommandAuthorization: define la clase de autorizaciones no permitidas
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class NotAllowedCommandAuthorization extends ProjectCommandAuthorization {

    public function canRun() {
        $this->errorAuth[self::ERROR_KEY] = TRUE;
        $this->errorAuth[self::EXCEPTION_KEY] =  'CommandAuthorizationNotFound';
        $this->errorAuth[self::EXTRA_PARAM_KEY] = NULL;

        return !$this->errorAuth[self::ERROR_KEY];
    }
}
