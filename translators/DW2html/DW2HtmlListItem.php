<?php
require_once "DW2HtmlParser.php";

class DW2HtmlListItem extends DW2HtmlBlock {

//    protected $openList = '';

//    protected $closeList = '';


//    protected function getReplacement($position) {
//
//        $ret = parent::getReplacement($position);
//
//
//        switch ($position) {
//            case IocInstruction::OPEN:
//                $ret = $this->openList . $ret;
//                break;
//
////            case IocInstruction::CLOSE:
////                $ret .= $this->closeList;
////                break;
//        }
//
//        return $ret;
//    }


    protected function getContent($token) {

        $return = '';

        preg_match($token['pattern'], $token['raw'], $match);
        $value = $match[1];

        preg_match("/^( *)/", $token['raw'], $spaces);
        $level = strlen($spaces[1]) / 2; // el nivell és igual al nombre d'espais

        $top = $this->getTopState();
        $prev = static::getPreviousState();

        // En el cas dels elements dintre del contenidor arrel el previ ha de ser el root
//        if (!$prev && $top) {
//            $prev = $top;
//        }

        // ALERTA el contenidor sempre ha de ser el top
        // quan un UL está dintre de un LI el top és aquest li

        $container = $top && $top['list'] ? $top : $prev;


        if (!$container || $container['level'] < $level) {
            // No hi ha cap element superior
            $containerToken = [];
            $containerToken['instruction'] = new DW2HtmlList();

            $extra = ['container' => $token['extra']['container']];
            $containerToken['instruction']->setExtra($extra);
            $containerToken['list'] = $token['extra'];
            $containerToken['state'] = 'list';
            $containerToken['level'] = $level;


            $this->pushState($containerToken);
            $return .= $containerToken['instruction']->getReplacement(self::OPEN);


        } else if ($container['list']) {
            // L'element superior es una llista, cal comprovar el nivell


            if ($container['level'] < $level) {
                // menor? cal afegir una llista a l'element anterior <-- TODO: això és igual que el cas anterior, mogut
                //      .
                //      ..


                // major?
            } else if ($container['level'] > $level) {
                //  ..
                //  .

                $return .= $container['instruction']->getReplacement(self::CLOSE); // Això ha de tancar la llista anterior
                $this->popState();
            }


            // ==
            // només cal afegir el item.

        }


        // Afegim l'item

        $class = static::$parserClass;
        $value = $class::getValue($value);

//        var_dump($this->getReplacement(self::OPEN));
//        die();

        $this->pushState($token);
        $return .= $this->getReplacement(self::OPEN) . $value;


        // TANCAMENT


        // el nextToken és un item-list?
        // SI:
        if ($this->nextToken['state'] == 'list-item') {


            preg_match("/^( *)/", $this->nextToken['raw'], $spaces);
            $nextTokenLevel = strlen($spaces[1]) / 2; // el nivell és igual al nombre d'espais


            //      La profunditat és igual
            //          afegimt el close
            if ($nextTokenLevel == $level) {
                $return .= $this->getReplacement(self::CLOSE);
                $this->popState();

            } else if ($nextTokenLevel < $level) {
                //      La profunditat del següent es menor?
                //      ..
                //      .
                //          s'ha de tancar aquest element i fer pop
                //          s'ha de tancar el container i fer pop
                $return .= $this->getReplacement(self::CLOSE);
                $this->popState();
                $return .= $this->getTopState()['instruction']->getReplacement(self::CLOSE);
                $this->popState();

                $auxTop = $this->getTopState();
                // ALERTA: no es suporta el cas en que dintre de un list-item puguin haver més d'una llista al mateix nivell, ja que això no es pot representar tampoc a DW.
                // si el top es de tipus listItem s'ha de tancar
                if ($auxTop['state'] == 'list-item') {
                    $return .= $auxTop['instruction']->getReplacement(self::CLOSE);
                    $this->popState();
                }

            }

            //      La profunditat del següent es major?
            //      .
            //      ..
            //          s'obrirà una nova llista, no cal fer res


        } else {
            // NO:
            // TODO: S'han de tancar tots els elements del stack de tipus list i list-item


            do {
                $top = $this->popState();
                $return .= $top['instruction']->getReplacement(self::CLOSE);

            } while ($this->getTopState()['state'] === 'list-item' || $this->getTopState()['state'] === 'list');
        }


        return $return;
    }






//    protected function getContent($token) {
//
//        preg_match($token['pattern'], $token['raw'], $match);
//        $value = $match[1];
//
//        preg_match("/^( *)/", $token['raw'], $spaces);
//        $level = strlen($spaces[1]) / 2; // el nivell és igual al nombre d'espais
//
//        $top = end(static::$stack);
//        $prev = static::getPreviousState();
//
//        // ALERTA: l'apertura i tancament de la llista no es pot fer aquí perque aquest valor es reparsejat i llavors es reinterpretarien les etiquetes
//        if (!$prev || $prev['list'] != $token['extra']['container'] || $prev['level'] < $level) {
//
//
//            // TODO: Problema els UL es coloquen a continuació d'aquest item, però s'han de ficar DINTRE del item anterior si l'anterior es un list-item. Si no hi ha $prev aques
//
//
//            $this->openList = '<' . $token['extra']['container'] . ">\n";
//
//
//            $newToken['list'] = $token['extra']['container'];
//            $token['level'] = $level;
//
//            // Hem de canviar l'ordre dels states
////            $aux = $this->popState();
//            $this->pushState($token);
//            $this->pushState($currentToke);
//
//
//        } else if ($prev['level'] > $level) {
////            $this->closeList = '</' . $token['extra']['container'] . ">\n";
//            $this->openList = '</' . $token['extra']['container'] . ">\n";
//
//
//            $aux = $this->popState();
//            $this->popState(); // aquesta es la llista
//            $this->pushState($aux); // tornem a inserir l'item, aquest es tancarà al parse
//
//        } else {
////            echo "ni obre ni tanca \n";
//        }
//
//        return $value;
//
//    }
//
//    protected function resolveOnClose($field) {
//        $return = $this->getReplacement(self::OPEN) . $field . $this->getReplacement(self::CLOSE);
//
//        // Si el següent token no és una llista la tanquem
//        if ($this->nextToken['state'] != 'list-item') {
//            do {
//
//                $return .= '</' . end(static::$stack)['extra']['container'] . ">\n";
//                // S'han de tancar en cascada fins que no quedi cap UL obert
//                $this->popState();
//
//            } while (end(static::$stack)['state'] == 'list-item');
//
//        }
//
//        return $return;
//    }
}