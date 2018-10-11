<?php
require_once "WiocclParser.php";
require_once "_WiocclCondition.php";

class _WiocclLoop
{

    protected $source;

    // FOR
    protected $counterName;
    protected $from;
    protected $to;
    protected $step;

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

    protected $type;

    public function __construct($value, $source)
    {
        $this->source = $source;

        if (substr($value, 0, 15) === "<WIOCCL:FOREACH") {
            $this->initializeIterator($value);
        } else if (substr($value, 0, 11) === "<WIOCCL:FOR") {
            $this->initializeLoop($value);
        } else {
            throw new Exception("Unknown iterator type");
        }

    }

    protected function initializeLoop($value)
    {
        $this->type = 'LOOP';
        $this->counterName = $this->source->extractVarName($value, "counter");
    }

    protected function initializeIterator($value)
    {
        $this->type = 'iterator';
        $this->varName = $this->source->extractVarName($value);
    }


    // TODO: Fer una funció genérica que seleccioni el loop o iterate segons el $type que s'ha autodefinit a la inicialització?

    public function loop($tokens, &$tokenIndex = 0, $from, $to, $step = 1)
    {
        $this->from = $from;
        $this->to = $to;
        $this->step = $step;

        return $this->parseTokensLoop($tokens, $tokenIndex);
    }

    // pel foreach
    public function iterate($tokens, &$tokenIndex = 0, $collection, $validator)
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

            $parsedValue = $this->source->parseToken($tokens, $tokenIndex);

            if ($parsedValue === null) { // tancament del foreach
                break;

            }
            $result .= $parsedValue;

            ++$tokenIndex;
        }
        return $result;
    }
}