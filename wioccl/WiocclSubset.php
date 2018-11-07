<?php
require_once "WiocclParser.php";

class WiocclSubset extends WiocclInstruction {

    protected $varName;
    protected $fullArray =[];
    protected $itemName;
    protected $filter;
    
    const FILTER_ATTR = "filter";    
    const ARRAY_ATTR = "array";    
    const ARRAY_ITEM_ATTR = "arrayitem";    
    const SUBSET_VAR_ATTR = "subsetvar";    

    public function __construct($value = null, $arrays = [], $dataSource, $generateSubset = true)
    {
        parent::__construct($value, $arrays, $dataSource);

        // varName correspón a la propietat var i es el nom de l'array
        // ALERTA! els arrays es llegeixen com un camp, la conversió d'array al seu valor es tracta al field

        $this->varName = $this->extractVarName($value, self::SUBSET_VAR_ATTR);
        $this->fullArray = $this->extractArray($value, self::ARRAY_ATTR);
        $this->itemName = $this->extractVarName($value, self::ARRAY_ITEM_ATTR);
//        $this->filterArgs = $this->extractFilterArgs($value);
        $strFilter = $this->extractVarName($value, self::FILTER_ATTR, true);
        $this->filter = new _WiocclCondition($strFilter);

        $subset = $this->generateSubset();
        $this->arrays[$this->varName] = $subset;

    }

    protected function generateSubset() {
        $subset = [];


        foreach ($this->fullArray as $row) {

            // TODO: Extreure a una funció a part per poder reutilizar al foreach, if i subset
            $this->arrays[$this->itemName] = $row;
            
            $this->filter->parseData($this->arrays, $this->dataSource);
            if ($this->filter->validate()) {
                $subset[] = $row;
            }
        }

        unset($this->arrays[$this->itemName]);

        return $subset;
    }
}