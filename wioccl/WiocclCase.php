<?php

class WiocclCase extends WiocclInstruction{
    const COND_ATTR = 'condition';
    const FORCHOOSE_ATTR = 'forchoose';
    const CASE_RETURN = '__parsedCase';

    public $updateParentArray = true;

    protected $chooseId;
    protected $index;

    public function __construct($value = null, &$arrays = [], $dataSource=[])
    {
        parent::__construct($value, $arrays, $dataSource);

//        $this->counterName = $this->extractVarName($value, "counter");
        $this->chooseId = $this->extractVarName($value, self::FORCHOOSE_ATTR, true);

        $value = str_replace("\\", "", $value);

        $this->index = count($this->arrays[$this->chooseId]);
        $this->arrays[$this->chooseId][] = [
            'condition' => $this->extractVarName($value, self::COND_ATTR, true)
        ];

//        $this->condition = $this->evaluateCondition($this->extractVarName($value, self::COND_ATTR, true));

    }

    public function parseTokens($tokens, &$tokenIndex)
    {

        $result = '';

        while ($tokenIndex < count($tokens)) {
            $parsedValue = $this->parseToken($tokens, $tokenIndex);

            if ($parsedValue === null) { // tancament del if
                break;

            } else {
                $result .= $parsedValue;
            }

            ++$tokenIndex;
        }

        $this->arrays[$this->chooseId][$this->index]['value'] = $result;

        return self::CASE_RETURN;
    }

}