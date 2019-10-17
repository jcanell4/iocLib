<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class Html2DWInstruction extends IocInstruction {

    protected static $parserClass = "Html2DWParser";

}
