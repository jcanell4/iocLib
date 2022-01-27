<?php

class WiocclParserData implements ParserDataInterface
{

    public function parse($text = null, $arrays = [], $datasource = [], &$resetables = NULL, $generateRoot = TRUE)
    {
        return WiocclParser::parse($text, $arrays, $datasource, $resetables);
    }

    public function getValue($text = null, $arrays = [], $datasource = [], &$resetables = NULL, $generateRoot = TRUE)
    {
        return WiocclParser::getValue($text, $arrays, $datasource, $resetables);
    }
}