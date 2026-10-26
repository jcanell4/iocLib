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

    // override
    // Si el token és un únic salt de línia i el darrer caràcter és un salt de línia, l'ignorem
    public function validateToken($token, $result) {
        $hasResultTrailingNewLine = substr($result, -1) == "\n";
        $isContent = $token['state'] == 'content';
        $isEmpty = $isContent && $token['value']=="\n";
        $validated = !$isContent || !($isEmpty && $hasResultTrailingNewLine);
        return $validated;
    }
}
