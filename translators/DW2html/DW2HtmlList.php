<?php
require_once "DW2HtmlParser.php";

class DW2HtmlList extends DW2HtmlMarkup {


    protected function getContent($token) {
        $return = '';

        preg_match($token['pattern'], $token['raw'], $match);
        $value = $match[1];

        preg_match("/^( *)/", $token['raw'], $spaces);
        $level = strlen($spaces[1]) / 2; // TODO: el nivell és igual al nombre d'espais

//        if (isset($token['extra']) && $token['extra']['remove-new-line'] === TRUE) {
//            $value = str_replace("\n", '', $value);
//        }

        $top = end(static::$stack);

//        var_dump($token);
//        die();

        if (count(static::$stack) == 0 || $top['list'] != $token['extra']['container'] || $top['level'] < $level) {
            $return .= '<' . $token['extra']['container'] . ">"; // TODO: Resoldre, si s'afegeix aquí un /n es converteix en un paràgraph
            $token['list'] = $token['extra']['container'];
            $token['level'] = $level;
            $this->pushState($token);

        } else if ($top['level'] > $level) {
            // TODO: Aquí s'ha de tancar!
            $return .= '</' . $token['extra']['container'] . ">"; // TODO: Resoldre, si s'afegeix aquí un /n es converteix en un paràgraph
            $this->popState();
        }

        $return .= $this->getReplacement(self::OPEN) . $value;

        return $return;
//        return $this->getReplacement(self::OPEN) . $value;
    }

    protected function resolveOnClose($field) {
        $return = $field . $this->getReplacement(self::CLOSE);

        // Si el següent token no és una llista la tanquem

        if ($this->nextToken['state'] != 'list-item') {
            do {

                $return .= '</' . end(static::$stack)['extra']['container'] . ">"; // TODO: Resoldre, si s'afegeix aquí un /n es converteix en un paràgraph
                // S'han de tancar en cascada fins que no quedi cap UL obert
                $this->popState();

            } while (end(static::$stack)['state'] == 'list-item');

        }

        return $return;
    }
}