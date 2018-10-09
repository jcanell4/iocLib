<?php
require_once "WiocclParser.php";
require_once "_WiocclCondition.php";

class WiocclIf extends WiocclParser
{

    protected $condition = false;

    public function __construct($value = null, $arrays = [], $dataSource)
    {
        parent::__construct($value, $arrays, $dataSource);

        $this->condition = (new _WiocclCondition($value, $arrays, $dataSource))->validate();

    }

    protected function parseTokens($tokens, &$tokenIndex=0)
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


        return ($this->condition ? $result : '');
    }
}