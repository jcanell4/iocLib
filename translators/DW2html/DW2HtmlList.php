<?php
require_once "DW2HtmlParser.php";

class DW2HtmlList extends DW2HtmlMarkup {

    protected $openList = '';
//    protected $closeList = '';


    protected function getReplacement($position) {

        $ret = parent::getReplacement($position);


        switch ($position) {
            case IocInstruction::OPEN:
                $ret = $this->openList . $ret;
                break;

//            case IocInstruction::CLOSE:
//                $ret .= $this->closeList;
//                break;
        }

        return $ret;
    }

    protected function getContent($token) {

        preg_match($token['pattern'], $token['raw'], $match);
        $value = $match[1];

        preg_match("/^( *)/", $token['raw'], $spaces);
        $level = strlen($spaces[1]) / 2; // el nivell és igual al nombre d'espais

        $top = end(static::$stack);

//        var_dump($token);
//        die();

        // ALERTA: l'apertura i tancament de la llista no es pot fer aquí perque aquest valor es reparsejat i llavors es reinterpretarien les etiquetes
        if (count(static::$stack) == 0 || $top['list'] != $token['extra']['container'] || $top['level'] < $level) {
            $this->openList = '<' . $token['extra']['container'] . ">\n";
            $token['list'] = $token['extra']['container'];
            $token['level'] = $level;
            $this->pushState($token);

        } else if ($top['level'] > $level) {
//            $this->closeList = '</' . $token['extra']['container'] . ">\n";
            $this->openList = '</' . $token['extra']['container'] . ">\n";

            $this->popState();
        }

        return $value;

    }

    protected function resolveOnClose($field) {
        $return = $this->getReplacement(self::OPEN) . $field . $this->getReplacement(self::CLOSE);

        // Si el següent token no és una llista la tanquem
        if ($this->nextToken['state'] != 'list-item') {
            do {

                $return .= '</' . end(static::$stack)['extra']['container'] . ">\n";
                // S'han de tancar en cascada fins que no quedi cap UL obert
                $this->popState();

            } while (end(static::$stack)['state'] == 'list-item');

        }

        return $return;
    }
}