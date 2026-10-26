<?php
require_once "DW2HtmlParser.php";

class DW2HtmlList extends DW2HtmlInstruction {

    // Pattern que ha d'incloure tots els possibles separadors (només hi ha 2)
    // Es farà servir amb regex així que cal escapar el *
    const separatorsPattern = "\*-";

    public $level = 0;

    public function open() {

        $top = $this->getTopState();
        $this->currentToken['instruction'] = $this;

        $raw = $this->currentToken['raw'];
        $i = strpos($raw, "  ");
        $refs = substr($raw, 0, $i);


        $return = $this->parseContent($refs);

        $separator = $this->extra['container'] == "ol"? '-' : "\*";
        $listItem = strstr($raw, "  " . $separator);


        $this->level = $this->getLevel($this->currentToken['raw']);
        $value = $listItem;


//        // TODO: Cas 0, el tipus de container és diferent <ul></ol> <-- aixó era el 3, marcat com TODO, però sembla que ja es va solucionar en altre banda
//

        if ($top
            && isset($top['extra'])
            && $top['extra']['container'] != $this->extra['container']
            && $top['instruction']->level == $this->level
        ) {
            $return .= $top['instruction']->close();

            array_pop(static::$stack);
            array_pop(WiocclParser::$structureStack);

            $openNew = true;
        }


        $topLevel = $top['instruction']->level;
        $currentLevel = $this->level;
        // Cas 1: no hi ha $top o el nivell del top es menor que aquest
        if ($openNew || !$top || $top['state'] !== 'list-item' || $top['instruction']->level < $this->level) {
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



        //Afegim el nou element de llista

        $itemToken = [];
        $itemToken['instruction'] = new DW2HtmlListItem($value);

//        $extra = ['container' => $this->currentToken['extra']['container'], 'level' => $this->level];


        $itemToken['instruction']->setTokens($this->currentToken, $this->nextToken);

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
        if ($token['state'] == 'close'
            || $token['state'] == 'content'
            || (isset($token['extra']) && $token['extra']['block'] && $token['state'] != 'list-item')
            || ($nextTokenLevel !== false && $nextTokenLevel < $this->level)) {
            return true;
        }

        return false;

    }

    protected function getValue($raw) {
        preg_match($this->currentToken['pattern'], $raw, $match);
        return $match[1];
    }

    // Si no hi ha com a mínim 2 espais és que no es tracta d'un element de llista, retornem false per poder
    // gestionar-ho
    protected function getLevel($raw) {
        $pattern = "/( *?)[" . self::separatorsPattern . "]/";
        if (preg_match($pattern, $raw, $spaces)) {
            $len = strlen($spaces[1]);
            if ($len>=2) {
                return $len / 2;
            }
        }

        return false;

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