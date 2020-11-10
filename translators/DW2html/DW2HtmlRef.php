<?php
require_once "DW2HtmlParser.php";

class DW2HtmlRef extends DW2HtmlMarkup {



//    public function isClosing($token) {
//
//
//        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
//            || ($token['action'] === 'close' && $token['state'] == $this->currentToken['state']
//                && $token['type'] == $this->currentToken['type'])) {
//
//            return true;
//        } else {
//            return false;
//        }
//
//    }


    protected $refId = -1;

    public function setTokens($tokens, $next) {
        parent::setTokens($tokens, $next);

        preg_match($this->currentToken['pattern'], $this->currentToken['raw'], $match);
        $this->refId = $match[1];

    }

    protected function getReplacement($position) {


        if (!$this->shouldRender()) {
            return '';
        }

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


    protected function shouldRender() {


        $structure = WiocclParser::getStructure();

        // Ignorem els nodes de tipus content
        if ($structure[$this->refId]->type === 'content') {
            return false;
        }

        // Amb això es consideran nodes fulles tots els node no tenen fills o els fills son de tipus content
//        foreach ($structure[$this->refId]->children as $child) {
//            if ($child->type !== 'content') {
//                return false;
//            }
//        }

//        $test = count($structure[$this->refId]->children);

//        return count($structure[$this->refId]->children) === 0;
        return true;
    }
}