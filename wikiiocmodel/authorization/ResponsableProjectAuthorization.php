<?php
/**
 * ResponsableProjectAuthorization: Extensión clase Autorización para los proyectos
 *                                 que tienen atributo de supervisor
 * 
 *  ALERTA!AQUESTA CLASSE NO ES POT ELIMINAR PERQUÈ ES NECESSITA PER CARREGAR ALGUNES AUTORITZACIONS!
 * 
  * @author Josep Cañellas
 */
if (!defined('DOKU_INC')) die();

class ResponsableProjectAuthorization extends ProjectCommandAuthorization {

    public function __construct() {
        parent::__construct();
        $this->allowedGroups[] = "manager";
        $this->allowedRoles[] = ProjectPermission::ROL_RESPONSABLE;
    }
}
