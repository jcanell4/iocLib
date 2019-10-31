<?php
require_once "DW2HtmlParser.php";

class DW2HtmlListItem extends DW2HtmlBlock {


    protected function getContent($token) {

        $return = '';

        preg_match($token['pattern'], $token['raw'], $match);
        $value = $match[1];

        preg_match("/^( *)/", $token['raw'], $spaces);
        $level = strlen($spaces[1]) / 2; // el nivell és igual al nombre d'espais

        $top = $this->getTopState();
        $prev = static::getPreviousState();

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
            // Es tancan totes les etiquetes obertes en cascada

            do {
                $top = $this->popState();
                $return .= $top['instruction']->getReplacement(self::CLOSE);

            } while ($this->getTopState()['state'] === 'list-item' || $this->getTopState()['state'] === 'list');
        }


        return $return;
    }


}