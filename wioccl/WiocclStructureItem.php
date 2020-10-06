<?php

class WiocclStructureItem {
    public $parent = null;
    public $children = [];
    public $rawValue = '';
    public $result = ''; // TODO: eliminar el resultat
    public $id = -1;

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