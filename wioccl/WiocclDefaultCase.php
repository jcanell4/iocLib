<?php

class WiocclDefaultCase extends WiocclCase {

    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$parentInstruction=NULL)
    {

        parent::__construct($value, $arrays, $dataSource, $parentInstruction, false);

        $this->index = count($this->arrays[$this->chooseId]);
        $this->chooseId = WiocclChoose::PREFIX . $this->extractVarName($value, self::FORCHOOSE_ATTR, true);

        $this->arrays[$this->chooseId][] = [
            'condition' => ['operator' => '==', 'rvalue' => 'true', 'lvalue' => 'true']
        ];
    }


}