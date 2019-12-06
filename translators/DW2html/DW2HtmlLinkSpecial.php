<?php
require_once "DW2HtmlParser.php";

class DW2HtmlLinkSpecial extends DW2HtmlInstruction {


    public function open() {
        $token = $this->currentToken;

        $anchor = '';

        if (preg_match($token['pattern'], $token['raw'], $match)) {
            $anchor = $match[1];
        }

        return '<a href="' . $anchor . '" contenteditable="false" data-ioc-link="' . $this->extra['type'] . '" title="' . $anchor . '">' . $anchor . '</a>&nbsp;';

    }


}