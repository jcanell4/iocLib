<?php

class WiocclStructureItem {
    public $parent = null;
    public $children = [];
    public $result = ''; // TODO: eliminar el resultat
    public $id = -1;


    // Només s'indica quan és relevant, per exemple per discriminar el content
    public $type = "";

    public $open = "";
    public $close = "";
    public $attrs = "";

    protected $structure;

    public function __construct(&$structure){
        $this->structure = &$structure;
    }

    public function getChildren() {
        $children = [];

        foreach ($this->children as $id) {
            $children[] = $this->structure[$id];
        }

        return $children;
    }

    public function getParent() {
        return $this->structure[$this->parent];
    }
}