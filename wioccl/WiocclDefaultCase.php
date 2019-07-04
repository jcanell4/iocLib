<?php

class WiocclDefaultCase extends WiocclCase {

    public function __construct($value = null, &$arrays = [], $dataSource=[])
    {

        parent::__construct($value, $arrays, $dataSource, false);

        $this->index = count($this->arrays[$this->chooseId]);
        $this->chooseId = WiocclChoose::PREFIX . $this->extractVarName($value, self::FORCHOOSE_ATTR, true);

        $this->arrays[$this->chooseId][] = [
            'condition' => ['operator' => 'true', 'rvalue' => '', 'lvalue' => '']
        ];
    }


}