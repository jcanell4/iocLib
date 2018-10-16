<?php
require_once "WiocclInstruction.php";
require_once "_WiocclCondition.php";

class WiocclIf extends WiocclInstruction
{

    protected $condition = false;

    public function __construct($value = null, $arrays = [], $dataSource, $parser)
    {
        parent::__construct($value, $arrays, $dataSource, $parser);

        $this->condition = (new _WiocclCondition($value, $this))->validate();

    }

    public function parseTokens($tokens, &$tokenIndex=0)
    {

        $result = '';

        while ($tokenIndex < count($tokens)) {
            $parsedValue = $this->parser->parseToken($tokens, $tokenIndex, $this);

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