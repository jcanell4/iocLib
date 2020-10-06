<?php
require_once "WiocclParser.php";

class WiocclSubset extends WiocclInstruction {

    protected $varName;
    protected $fullArray =[];
    protected $itemName;
    protected $filter;
    protected $fieldName; // això s'utilitza per crear un array que contingui només els valors del camp seleccionat

    const FILTER_ATTR = "filter";    
    const ARRAY_ATTR = "array";    
    const ARRAY_ITEM_ATTR = "arrayitem";    
    const SUBSET_VAR_ATTR = "subsetvar";
    const FIELD_VAR_ATTR = "field";

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL)
    {
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        // varName correspón a la propietat var i es el nom de l'array
        // ALERTA! els arrays es llegeixen com un camp, la conversió d'array al seu valor es tracta al field

        // Com que el subset només serveix per establir valors no generem nodes a la estructura per de cap atribut
        $class = (static::$parserClass);
        $prev = $class::$generateStructure;
        $class::$generateStructure = false;




        $this->varName = $this->extractVarName($value, self::SUBSET_VAR_ATTR);
        $this->fullArray = $this->extractArray($value, self::ARRAY_ATTR);
        $this->itemName = $this->extractVarName($value, self::ARRAY_ITEM_ATTR);
//        $this->filterArgs = $this->extractFilterArgs($value);
        $strFilter = $this->extractVarName($value, self::FILTER_ATTR, true);

        $this->fieldName = $this->extractVarName($value, self::FIELD_VAR_ATTR, false);


        $this->filter = new _WiocclCondition($strFilter);


        $subset = $this->generateSubset();
        $this->arrays[$this->varName] = $subset;



        $class::$generateStructure = $prev;

    }

    protected function generateSubset() {
        $subset = [];


        foreach ($this->fullArray as $row) {

            // TODO: Extreure a una funció a part per poder reutilizar al foreach, if i subset
            $this->arrays[$this->itemName] = $row;
            
            $this->filter->parseData($this->arrays, $this->dataSource, $this->resetables);
            if ($this->filter->validate()) {

                if ($this->fieldName) {
                    $subset[] = $row[$this->fieldName];
                } else {
                    $subset[] = $row;
                }

            }
        }

        unset($this->arrays[$this->itemName]);

        return $subset;
    }
}