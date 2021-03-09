<?php

class WiocclStructureItem
{
    public $parent = null;
    public $children = [];
    public $result = ''; // TODO: eliminar el resultat
    public $id = -1;
    public $isClone = false;


    // Només s'indica quan és relevant, per exemple per discriminar el content
    public $type = "";

    public $open = "";
    public $close = "";
    public $attrs = "";

    protected $structure;

    public function __construct(&$structure, $init = [])
    {
        $this->structure = &$structure;

        if (count($init) > 0) {

            $test = $init['type'] === 'readonly';

            $this->parent = $init['parent'];
            $this->children = $init['children'];
            $this->result = $init['result'];
            $this->id = $init['id'];
            $this->isClone = $init['isClone'];
            $this->type = $init['type'];
            $this->open = $init['open'];
            $this->close = $init['close'];
            $this->attrs = $init['attrs'];

            $this->attrs = preg_replace('/&escapedgt;/', '\\>', $init['attrs']);
        }
    }

    public function getChildren()
    {
        $children = [];

        foreach ($this->children as $id) {
            $children[] = $this->structure[$id];
        }

        return $children;
    }

    public function getParent()
    {
        return $this->structure[$this->parent];
    }

    public function toWioccl()
    {

        // si es un clon o el parent es null no es renderitza ($this->parent == null identifica al root, també podria ser $this->id == 0
        if ($this->isClone || $this->parent == null) {
            return '';
        }

        // ALERTA! no es poden fer servir % dintre dels atributs perquè és el simbol que s'utilitza per fer els reemplaços
        // TODO: Considerar afegir un escaped % per aquest cas
        // En el cas del content no es fa mai la substitució, així que es pot fer servir el %.
        if ($this->type == "content") {
            $text = $this->open;
        } else {
            $text = sprintf($this->open, $this->attrs);
        }


        $children = '';

        foreach ($this->children as $childId) {
            $children .= $this->structure[$childId]->ToWioccl();
        }

        $text .= $children . $this->close;

        Html2DWWioccl::$processedRefs[] = $this->id;
        return $text;

    }
}