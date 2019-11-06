<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlInstruction extends IocInstruction {

    protected static $parserClass = "DW2HtmlParser";
}
