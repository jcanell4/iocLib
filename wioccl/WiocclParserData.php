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

    public static function normalizeValue($text = null)
    {
        if (is_array($text) || is_object($text)) {
            $text = json_encode($text);
        } else if (is_bool($text)) {
            $text = $text ? "true" : "false";
//        }else if(is_string($ret)){
//            $ret = "\"$ret\"";
        }
        return $text;

    }
}