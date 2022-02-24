<?php
require_once "DW2HtmlParser.php";

class DW2HtmlRef extends DW2HtmlMarkup {

    protected $refId = -1;

    public function setTokens($tokens, $next) {
        parent::setTokens($tokens, $next);

        preg_match($this->currentToken['pattern'], $this->currentToken['raw'], $match);
        $this->refId = $match[1];



        // ALERTA! Només afegim a la pila els elements que no siguin de tipus content
        $structure = WiocclParser::getStructure();
        if ($structure[$this->refId]->type === 'content') {
            return ;
        }


        if ($this->currentToken['state'] === 'ref-open') {
            array_push(WiocclParser::$structureStack,$this->refId);
        }  else if ($this->currentToken['state'] === 'ref-close'){
            $top = array_pop(WiocclParser::$structureStack);


            if ($top !== $this->refId) {
                $stack = WiocclParser::$structureStack;
                var_dump($top, $this->refId, $stack);
                throw new MissingClosingTranslatorException(htmlspecialchars($top . " -> " . $this->refId));
            }


        } else {
            die ('unimplemented');
        }

    }

    protected function getReplacement($position) {


        if (!$this->shouldRender()) {
            return '';
        }

        $pattern = $this->currentToken['pattern'];

        preg_match($pattern, $this->currentToken['raw'], $match);



        switch ($position) {
            case IocInstruction::OPEN:
                $tag = sprintf($this->extra['replacement'][0], $match[1]);
                $this->addRefId($tag);
                return $tag;


            case IocInstruction::CLOSE:
                return $this->extra['replacement'][1];
        }

        return 'ERROR: unknown position: ' . $position;
    }


    protected function shouldRender() {


        $structure = WiocclParser::getStructure();

        // Ignorem els nodes de tipus content
        if ($structure[$this->refId]->type === 'content' || $this->currentToken['state'] === 'ref-close') {
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