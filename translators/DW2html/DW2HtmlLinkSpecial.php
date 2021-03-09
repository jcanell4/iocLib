<?php
require_once "DW2HtmlParser.php";

class DW2HtmlLinkSpecial extends DW2HtmlInstruction {


    public function open() {
        $token = $this->currentToken;

        $anchor = '';

        if (preg_match($token['pattern'], $token['raw'], $match)) {
            $anchor = $match[1];
        }

        $sanitized = $this->sanitize($anchor);


        return '<a href="' . $sanitized . '" contenteditable="false" data-ioc-link="' . $this->extra['type'] . '" title="' . $sanitized . '">' . $this->parseContent($anchor) . '</a>';

    }

    // això és necessari perquè els enllaços no han de contenir referències
    public function sanitize($anchor) {
        return preg_replace('/\[\/?ref=\d]/ms', '', $anchor);


    }


}