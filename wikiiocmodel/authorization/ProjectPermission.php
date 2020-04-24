<?php
/**
 * Permission: la clase gestiona los permisos de usuario en este proyecto
 * @author Rafael Claver
 */
if (!defined('DOKU_INC') ) die();

class ProjectPermission extends BasicPermission {

    const ROL_RESPONSABLE = "responsable";
    const ROL_AUTOR       = "autor";
    const ROL_SUPERVISOR  = "supervisor";

    const ROL_RESPONSABLE_ORDER = 0;
    const ROL_AUTOR_ORDER       = 1;
    const ROL_SUPERVISOR_ORDER  = 2;

    protected $responsable = [];
    protected $author = [];
    protected $rol = [];

    protected $aRoles = [self::ROL_RESPONSABLE => self::ROL_RESPONSABLE_ORDER,
                         self::ROL_AUTOR       => self::ROL_AUTOR_ORDER,
                         self::ROL_SUPERVISOR  => self::ROL_SUPERVISOR_ORDER
                        ];

    public function getResponsable() {
        return $this->responsable;
    }

    public function getAuthor() {
        return $this->author;
    }

    /**
     * Devuelve el string ROL de mayor precedencia o un array con todos los roles (valores string)
     * @param boolean $all : TRUE indica que se desea todo el array de roles
     * @return string|array : rol de mayor precedencia o un array con todos los roles
     */
    public function getRol($all=FALSE) {
        if (!empty($this->rol)) {
            if ($all) {
                foreach ($this->rol as $order) {
                    $ret[] = array_search($order, $this->aRoles);
                }
            }else {
                $ret = array_search($this->rol[0], $this->aRoles);
            }
        }
        return $ret;
    }

    /**
     * Devuelve el numeric ROL de mayor precedencia o todo el array de roles (claves numéricas)
     * @param boolean $all : TRUE indica que se desea todo el array de roles
     * @return numeric|array : rol de mayor precedencia o todo el array (claves numéricas)
     */
    public function getRolOrder($all=FALSE) {
        return ($all) ? $this->rol : $this->rol[0];
    }

    public function setResponsable($responsable) {
        if (is_string($responsable) && !empty($responsable)){
            $this->responsable = preg_split("/[\s,]+/", $responsable);
        }
    }

    public function setAuthor($author) {
        if (is_string($author) && !empty($author)){
            $this->author = preg_split("/[\s,]+/", $author);
        }
    }

    /**
     * Añade roles al array $this->rol
     * El array $this->rol contiene los valores numéricos de los roles ordenados de menor a mayor
     * el valor menor es el rol más importante (mayor precedencia)
     * La precedencia (mayor importancia) está definida en $this->aRoles
     * @param string|array $rol : sólo pueden ser valores predefinidos como constante
     */
    public function setRol($rol) {
        if (is_array($rol)) {
            foreach ($rol as $r) {
                if (isset($this->aRoles[$r])) {
                    $this->rol[] = $this->aRoles[$r];
                }
            }
        }else {
            if (isset($this->aRoles[$rol])) {
                $this->rol[] = $this->aRoles[$rol];
            }
        }
        if (!empty($this->rol)) {
            sort($this->rol, SORT_NUMERIC);
        }
    }

}
