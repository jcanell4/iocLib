<?php
require_once "DW2HtmlParser.php";

class DW2HtmlReference extends DW2HtmlMarkup {

    protected $refId = -1;

    public function setTokens($tokens, $next) {
        parent::setTokens($tokens, $next);

        preg_match($this->currentToken['pattern'], $this->currentToken['raw'], $match);
        $this->refId = $match[1];

    }

    protected function getReplacement($position) {

        $pattern = $this->currentToken['pattern'];

        preg_match($pattern, $this->currentToken['raw'], $match);

        switch ($position) {
            case IocInstruction::OPEN:
                return sprintf($this->extra['replacement'][0], $match[1]);


            case IocInstruction::CLOSE:
                return $this->extra['replacement'][1];
        }

        return 'ERROR: unknown position: ' . $position;
    }

    public function isClosing($token) {

        // ERROR, amb això no es controlen els elements que no respecten xml: només es tanca quan es troba el token d'apertura corresponent, que ha de ser del mateix tipus i amb action close




        if ($token['action'] === 'close' && $token['type'] == $this->currentToken['type']) {
            preg_match($token['pattern'], $token['raw'], $match);
            return $match[1] == $this->refId;
        }


    }
}