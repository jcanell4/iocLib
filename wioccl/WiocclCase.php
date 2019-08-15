<?php

class WiocclCase extends WiocclInstruction {
//    const COND_ATTR = 'condition';
    const COND_ATTR = 'relation';
    const FORCHOOSE_ATTR = 'forchoose';
    const LEXPRESSION = 'lExpression';
    const REXPRESSION = 'rExpression';
    const RELATION = 'relation';

    protected $chooseId;
    protected $index;

    public function __construct($value = null, $arrays = [], $dataSource = [], &$parentInstruction=NULL, $mandatoryCondition = true) {
        parent::__construct($value, $arrays, $dataSource, $parentInstruction);

        $this->chooseId = WiocclChoose::PREFIX . $this->extractVarName($value, self::FORCHOOSE_ATTR, true);

        $value = str_replace("\\", "", $value);

        $this->index = count($this->arrays[$this->chooseId]);

        if ($mandatoryCondition) {
            $this->arrays[$this->chooseId][] = [
                'condition' => [
                    'lvalue' => $this->extractVarName($value, self::LEXPRESSION, false),
                    'rvalue' => $this->extractVarName($value, self::REXPRESSION, false),
                    'operator' => $this->extractVarName($value, self::RELATION, false)
                ]
            ];
        }

    }
    
    protected function resolveOnClose($result) {
        $this->arrays[$this->chooseId][$this->index]['value'] = $result;
        $this->arrays[$this->chooseId][$this->index]['updatableInstructions'] = &$this->updatableInstructions;
        $this->updateParentArray(self::FROM_CASE, $this->chooseId);
        return "";
    }

    public function setUpdatableInstructions($ui){
      $this->updatableInstructions= $ui;
    }

//    public function parseTokens($tokens, &$tokenIndex) {
//
//        $result = '';
//
//        while ($tokenIndex < count($tokens)) {
//            $parsedValue = $this->parseToken($tokens, $tokenIndex);
//
//            if ($parsedValue === null) { // tancament del if
//                break;
//
//            } else {
//                $result .= $parsedValue;
//            }
//
//            ++$tokenIndex;
//        }
//
//        $this->arrays[$this->chooseId][$this->index]['value'] = $result;
//        
//        return true;
//    }

}