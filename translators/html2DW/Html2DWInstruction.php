<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWInstruction extends IocInstruction {

    protected static $parserClass = "Html2DWParser";

    public function parseTokens($tokens, &$tokenIndex = 0)
    {
        Logger::debug("\n### HTML2DW TOKENS START ###\n" . json_encode($tokens) . "\n### HTML2DW TOKENS END ###\n", 0, __LINE__, basename(__FILE__), 1, true);

        return parent::parseTokens($tokens, $tokenIndex);
    }
}
