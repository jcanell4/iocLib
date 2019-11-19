<?php
require_once "DW2HtmlParser.php";

class DW2HtmlTable extends DW2HtmlInstruction {


    public $level = 0;

    public function open() {

        $return = '';


//        $return = "table\n";
        $value = $this->getValue($this->currentToken['raw']);

        $top = $this->getTopState();
        $this->currentToken['instruction'] = $this;

        // No es permet l'ús de taules imbricades, ignorem aquesta posibilitat
        if (!$top) {
            // Obrim la taula
            $return .= $this->getReplacement(self::OPEN);
            $this->pushState($this->currentToken);

        }
//        else if ($top['state'] == 'table') {
//            // Ja s'ha obert abans, és una nova fila
//
//
//        }


        $itemToken = [];
        $itemToken['instruction'] = new DW2HtmlRow($value);


        $itemToken['instruction'] ->setTokens($this->currentToken, $this->nextToken);

        $extra = ['container' => $this->currentToken['extra']['container'], 'level' => $this->level, 'replacement' => $this->currentToken['extra']['replacement']];
        $itemToken['instruction']->setExtra($extra);
        $itemToken['state'] = 'row';


        $this->pushState($itemToken);
        $return .= $itemToken['instruction']->open();

        return "<table>\n" . $return . "</table>";
    }



    public function isClosing($token) {

        if ($token['state'] != 'content' && $token['state'] != 'table') {
            return true;
        } else {
            return false;
        }

    }

    protected function getValue($raw) {
        preg_match($this->currentToken['pattern'], $raw, $match);
        return $match[1];
    }

//    protected function getLevel($raw) {
//        preg_match("/^( *)/", $raw, $spaces);
//        return strlen($spaces[1]) / 2;
//    }


//    protected function getReplacement($position) {
//
//        switch ($position) {
//            case IocInstruction::OPEN:
//
//                return '<' . $this->extra['container'] . ">";
//
//            case IocInstruction::CLOSE:
//                return '</' . $this->extra['container'] . ">";
//        }
//
//        return 'ERROR: unknown position: ' . $position;
//    }

}