<?php
require_once "WiocclParser.php";
require_once "_WiocclCondition.php";

class _WiocclLoop
{

    protected $source;
    protected $type;

    // FOR
    protected $counterName;
    protected $from;
    protected $to;
    protected $step;
    protected $defaultStep=1;

    // FOREACH
    // Els format dels arrays al fullArray es similar a (sense els index 0, 1, etc):
    //            '[
    //                '0' => ['tipus' => 'lalala', 'eina' => 'ggg', 'opcionalitat' => 'cap'],
    //                '1' => ['tipus' => 'oooo', 'eina' => 'elelel', 'opcionalitat' => 'no']
    //            ]

    // El format que es passa a arrays es:
    //      ['tipus' => 'lalala', 'eina' => 'ggg', 'opcionalitat' => 'cap']

    protected $fullArray;
    protected $varName;
    protected $validator;
    protected $parser;




    public function __construct($value, $parser, $source = null)
    {
        $this->parser = $parser;

        $this->source = $source === null ? $parser : $source;


        if (substr($value, 0, 15) === "<WIOCCL:FOREACH") {
            $this->initializeIterator($value);
        } else if (substr($value, 0, 11) === "<WIOCCL:FOR") {
            $this->initializeLoop($value);
        }

    }

    protected function initializeLoop($value)
    {
        $this->type = 'LOOP';
        $this->counterName = $this->parser->extractVarName($value, "counter");
    }

    protected function initializeIterator($value)
    {
        $this->type = 'ITERATOR';
        $this->varName = $this->parser->extractVarName($value);
    }


    public function loop($tokens, &$tokenIndex = 0, $arg1, $arg2, $arg3 = null)
    {
        switch ($this->type) {
            case 'LOOP':
                return $this->runLoop($tokens, $tokenIndex, $arg1, $arg2, $arg3);

            case 'ITERATOR':
                return $this->runIterate($tokens, $tokenIndex, $arg1, $arg2);

            default:
                return '[ERROR: Undefined iterator type]';
        }
    }

    public function runloop($tokens, &$tokenIndex = 0, $from, $to, $step)
    {
        $this->from = $from;
        $this->to = $to;
        $this->step = $step !== null ? $step : $this->defaultStep;

        return $this->parseTokensLoop($tokens, $tokenIndex);
    }


    public function runIterate($tokens, &$tokenIndex = 0, $collection, $validator)
    {
        $this->fullArray = $collection;
        $this->validator = $validator;
        return $this->parseTokensIterator($tokens, $tokenIndex);
    }

    public function parseTokensIterator($tokens, &$tokenIndex = 0)
    {

        $result = '';
        $startTokenIndex = $tokenIndex;
        $lastBlockIndex = null;
        $lastTokenIndex = 0;

        for ($arrayIndex = 0; $arrayIndex < count($this->fullArray); $arrayIndex++) {

            $tokenIndex = $startTokenIndex;
            $row = $this->fullArray[$arrayIndex];
            $this->source->arrays[$this->varName] = $row;

            $process = $this->validator->validate();

            if (!$process && $lastTokenIndex > 0) {
                // Ja s'ha processat previament el token de tancament i no s'acompleix la condició, no cal continuar processant
                continue;
            }

            $result .= $this->parseTokensOfItem($tokens, $tokenIndex);


            $lastTokenIndex = $tokenIndex;

        }

        $tokenIndex = $lastTokenIndex;

        return $result;
    }


    protected function parseTokensLoop($tokens, &$tokenIndex)
    {

        $result = '';
        $startTokenIndex = $tokenIndex;
        $lastBlockIndex = null;
        $lastTokenIndex = 0;

        if ($this->from > $this->to) {
            $this->parseTokensOfItem($tokens, $tokenIndex);
        } else {
            for ($arrayIndex = $this->from; $arrayIndex <= $this->to; $arrayIndex += $this->step) {

                $tokenIndex = $startTokenIndex;
                $this->source->arrays[$this->counterName] = $arrayIndex;

                $result .= $this->parseTokensOfItem($tokens, $tokenIndex);

                $lastTokenIndex = $tokenIndex;

            }

            $tokenIndex = $lastTokenIndex;
        }

        $tokenIndex = $lastTokenIndex;

        return $result;
    }

    protected function parseTokensOfItem($tokens, &$tokenIndex)
    {
        $result = '';
        while ($tokenIndex < count($tokens)) {

            $parsedValue = $this->parser->parseToken($tokens, $tokenIndex, $this->source);

            if ($parsedValue === null) { // tancament del foreach
                break;

            }
            $result .= $parsedValue;

            ++$tokenIndex;
        }
        return $result;
    }
}