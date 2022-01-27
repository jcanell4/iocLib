<?php
include_once(DOKU_LIB_IOC . '/common/utility/_AbstractConditions.php');

class _WiocclCondition extends _BaseCondition
{

    public function __construct($strCondition)
    {
        $parser = new WiocclParserData();
        parent::__construct($strCondition, $parser);
    }

}
