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
        if (!$top || $top['instruction']->level < $this->level) {
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

//        die("no es crida el isClosing");

        // ALERTA! El isClosing se llama cuando se hace el GetValue dentro del list item


        // ALERTA! Ahora no se está llamando ¿?¿?¿
//        var_dump(static::$stack);
//        die('isClosing?');






        // Replantejament, així evitem haver de fer canvis de posició al stack, el token el que afegeix és una llista
        // Cas 1: El pattern inclou el salt de línia, així que el següent token ha de ser un list-item si continua la llista
        if ($token['state'] !== 'list-item'
            && isset($token['extra']) && $token['extra']['block'] === TRUE
        ){
            var_dump($this->currentToken);
            var_dump($token);

            // TODO: ALERTA! ara el li es tanca correctament però es tanca el UL cada vegada

            die("següent no és list-item");

            return true;
        }

        // Cas 2: el nextToken és llista però d'un nivell inferior a l'actual
        //      ..
        //      .
        $nextTokenLevel = $this->getLevel($token['raw']);
        if ($nextTokenLevel < $this->level) {
            var_dump($token);
            var_dump($nextTokenLevel, $this->level);
//            die('el $nextTokenlevel no és < que $this-> level');
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
                return '<' . $this->extra['container'] . ">\n";

            case IocInstruction::CLOSE:
                return '</' . $this->extra['container'] . ">\n";
        }

        return 'ERROR: unknown position: ' . $position;
    }

}