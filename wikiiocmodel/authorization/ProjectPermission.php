<?php
/**
 * Permission: la clase gestiona los permisos de usuario en este proyecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC') ) die();

class ProjectPermission extends BasicPermission {

    const ROL_RESPONSABLE = "responsable";
    const ROL_AUTOR = "autor";

    protected $author;        //array
    protected $responsable;   //array
    protected $rol;

    public function getAuthor() {
        return $this->author;
    }

    public function getResponsable() {
        return $this->responsable;
    }

    public function getRol() {
        return $this->rol;
    }

    public function setAuthor($author) {
        if(is_string($author) && !empty($author)){
            $this->author = preg_split("/[\s,]+/", $author);
        }
    }

    public function setResponsable($responsable) {
        if(is_string($responsable) && !empty($responsable)){
            $this->responsable = preg_split("/[\s,]+/", $responsable);
        }
    }

    public function setRol($rol) {
        $this->rol = $rol;
    }

}
