<?php

interface ParserDataInterface
{
    public function parse($text = null, $arrays = [], $dataSource = [], &$resetables=NULL, $generateRoot = TRUE);
    public function getValue($text = null, $arrays = [], $dataSource = [], &$resetables=NULL, $generateRoot = TRUE);

    public static function normalizeValue($text = null);

}