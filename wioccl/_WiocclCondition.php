<?php

class _WiocclCondition extends _BaseCondition
{

    public function __construct($strCondition)
    {
        $parser = new WiocclParserData();
        parent::__construct($strCondition, $parser);
    }

}
