<?php
require_once "DW2HtmlParser.php";

class DW2HtmlList extends DW2HtmlInstruction {


    public $level = 0;

    public function open() {
        $return = '';

        $top = $this->getTopState();
        $this->currentToken['instruction'] = $this;


        $this->level = $this->getLevel($this->currentToken['raw']);
        $value = $this->getValue($this->currentToken['raw']);

        // Cas 1: no hi ha $top o el nivell del top es menor que aquest
        if (!$top || $top['state'] !== 'list-item' || $top['instruction']->level < $this->level) {
            // Obrim la llista

//            if ($top) {
//                var_dump($top);
//                echo $this->level . ' < ' . $top['instruction']->level;
//                die("hi ha top i es menor ");
//            }

            $return .= $this->getReplacement(self::OPEN);
            $this->pushState($this->currentToken);

        }

        // Cas 2: el top és una llista amb el mateix nivell (no pot ser major perquè s'hauria tancat)


        // TODO: Cas 3, el tipus de container és diferent? <ul></ol>

        //Afegim el nou element de llista

        $itemToken = [];
        $itemToken['instruction'] = new DW2HtmlListItem($value);

//        $extra = ['container' => $this->currentToken['extra']['container'], 'level' => $this->level];


        $itemToken['instruction'] ->setTokens($this->currentToken, $this->nextToken);

        $extra = ['container' => $this->currentToken['extra']['container'], 'level' => $this->level, 'replacement' => $this->currentToken['extra']['replacement']];
        $itemToken['instruction']->setExtra($extra);
        $itemToken['list'] = $this->currentToken['extra'];
        $itemToken['state'] = 'list';
        $itemToken['level'] = $this->level;


        $this->pushState($itemToken);
        $return .= $itemToken['instruction']->open();


        return $return;
    }



    public function isClosing($token) {


        $nextTokenLevel = $this->getLevel($token['raw']);
        if ($nextTokenLevel < $this->level) {
            return true;
        }

        return false;

    }

    protected function getValue($raw) {
        preg_match($this->currentToken['pattern'], $raw, $match);
        return $match[1];
    }

    protected function getLevel($raw) {
        preg_match("/^( *)/", $raw, $spaces);
        return strlen($spaces[1]) / 2;
    }


    protected function getReplacement($position) {

        switch ($position) {
            case IocInstruction::OPEN:

                // Afegim la referència si escau
                $tag = '<' . $this->extra['container']. ">";
                $this->addRefId($tag);

                return $tag;

            case IocInstruction::CLOSE:
                return '</' . $this->extra['container'] . ">";
        }

        return 'ERROR: unknown position: ' . $position;
    }

}