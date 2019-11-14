<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlParagraph extends DW2HtmlInstruction {


    public function isClosing($token) {
        // un doble salt de línia sempre tanca un paràgraf

        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
            || $token['raw'] === "\n\n") {

            return true;
        } else {

            return false;
        }

    }
}
