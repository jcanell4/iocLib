<?php

abstract class WiocclInstruction
{

    protected $parser;
    public $arrays;
    public $dataSource;
    protected $rawValue;

    public function __construct($value, $arrays, $dataSource, $parser)
    {
        $this->arrays = $arrays;
        $this->dataSource = $dataSource;
        $this->parser = $parser;
        $this->rawValue = $value;
    }

    // Aquest métode es cridat pel parser
    public function getTokensValue($tokens, &$tokenIndex)
    {
        return $this->parseTokens($tokens, $tokenIndex);
    }

    abstract protected function parseTokens($tokens, &$tokenIndex);
}