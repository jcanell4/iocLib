<?php
require_once "WiocclParser.php";

abstract class WiocclInstruction
{

    protected $parser;

    public function __construct($value, $arrays, $dataSource)
    {
        $this->parser = new WiocclParser($value, $arrays, $dataSource);
    }

    // Aquest métode es cridat pel parser
    public function getTokensValue($tokens, &$tokenIndex)
    {
        return $this->parseTokens($tokens, $tokenIndex);
    }

    // Implementació per defecte. Aquest métode pot ser cridat pel parser o per la propia classe
    protected function parseTokens($tokens, &$tokenIndex)
    {
        return $this->parser->parseTokens($tokens, $tokenIndex);
    }
}