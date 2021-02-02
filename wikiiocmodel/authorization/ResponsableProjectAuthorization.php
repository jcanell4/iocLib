<?php
/**
 * SupervisorProjectAuthorization: Extensión clase Autorización para los proyectos
 *                                  con una autorización por roles y grupos
  * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ResponsableProjectAuthorization extends ProjectCommandAuthorization {
     public function __construct() {
        parent::__construct();
        $this->allowedRoles[] = ProjectPermission::ROL_RESPONSABLE;
    }

}
