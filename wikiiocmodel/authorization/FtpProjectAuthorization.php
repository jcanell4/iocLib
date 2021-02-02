<?php
/**
 * FtpProjectAuthorization: Extensión clase Autorización para los comandos
 *      con una autorización por roles y grupos
 *
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class FtpProjectAuthorization extends ProjectCommandAuthorization {

    public function __construct() {
        parent::__construct();
        $this->allowedGroups[] = "manager";
        $this->allowedRoles[] = ProjectPermission::ROL_AUTOR;
    }

}
