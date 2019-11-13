<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlInstruction extends IocInstruction {

    protected static $parserClass = "DW2HtmlParser";

    protected function resolveOnClose($result) {

        die("Aquest tipus d'instrucció no fa servir resolveOnClose");
    }

    public function open() {

        return $this->getReplacement(self::OPEN);
    }

    public function close() {
        return $this->getReplacement(self::CLOSE);
    }


    public function isClosing($token) {
        die("Unimplemented");
    }

    public function parseToken($tokens, &$tokenIndex) {



        $currentToken = $tokens[$tokenIndex];
        $nextToken = $tokenIndex + 1 < count($tokens) ? $tokens[$tokenIndex + 1] : NULL;
        $result = '';

        if ($currentToken['state'] == 'content') {
            $action = 'content';
            $currentToken['class'] = 'DW2HtmlContent';

        } else {
            $action = $currentToken['action'];
        }


        $top = end(static::$stack);

        if ($action == 'open-close') {
            // Si l'ultim element del stack es del mateix tipus el tanca


            if (count(static::$stack) > 0 && $top['state'] == $currentToken['state'] && $top['type'] == $currentToken['type']) {
//                var_dump($top);
                $currentToken['action'] = $action = 'close';

//                die('open-close: close');
            } else {
//                var_dump($top);

                $currentToken['action'] = $action = 'open';
            }

        }


        if (!$top && $currentToken['action'] == 'close' && $currentToken['state'] == 'paragraph') {
            // Aques és el cas de trobarse múltiples salts de línia que és un tancament sense abertura
            // ALERTA! També entra amb els salts de línia simple

            $newContainerToken = DW2HtmlParser::$defaultContainer;
            $container = $this->getClassForToken($newContainerToken, $nextToken);
            $newContainerToken['instruction'] = $container;
            $this->pushState($newContainerToken);
            $result .= $container->open();
//                    die ("no hi ha top");
            $top = end(static::$stack);
        }

//

        // Si és un salt de línia s'ha de tornar a afegir, i s'ha de fer abans de tancar el token anterior
        if ($currentToken['raw'] == "\n" ) {
            $result .= $currentToken['raw'];
        }



        while ($top && $top['instruction']->isClosing($currentToken)) {

//            var_dump($top);
//            echo "TANCANT\n";

            $result .= $top['instruction']->Close();
            $this->popState();
            $top = end(static::$stack);
//            var_dump($result);
        }


        // Aquest cas es dona quan una línia comença per una etiqueta de tipus inline (no és block)
        if (!$top && isset($currentToken['extra']) && $currentToken['extra']['block'] !== TRUE && $currentToken['action'] !== 'close') {

            $newContainerToken = DW2HtmlParser::$defaultContainer;
            $container = $this->getClassForToken($newContainerToken, $nextToken);
            $newContainerToken['instruction'] = $container;
            $this->pushState($newContainerToken);
            $result .= $container->open();
//                    die ("no hi ha top");
            $top = end(static::$stack);
        }




//        var_dump($currentToken);

        switch ($action) {
            case 'content':

                if (!$top) {

                    $newContainerToken = DW2HtmlParser::$defaultContainer;
                    $container = $this->getClassForToken($newContainerToken, $nextToken);
                    $newContainerToken['instruction'] = $container;
                    $this->pushState($newContainerToken);
                    $result .= $container->open();
//                    die ("no hi ha top");

                }


                $item = $this->getClassForToken($currentToken, $nextToken);

//                var_dump($item);
//                die("stop");

                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);

                // ALERTA: Els salts de línia s'afegeixen directament, sense processar
                if ($currentToken['value'] == "\n") {
                    $result .= $currentToken['value'];
                } else {
                    $result .= $item->getContent($currentToken);
                }
                $this->popState();


                break;


            case 'open':

//                $mark = self::$instancesCounter == 0;
//                self::$instancesCounter++;
                $item = $this->getClassForToken($currentToken, $nextToken);

                $result .= $item->open();

//                die ($result);
                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);
//                die('opener');


                // ALERTA[Xavi] Això és necessari? és el mateix en tots els casos i no es fa servir en cap altre lloc el $instancesCounter
//                if ($mark) {
//                    $result .= $item->getTokensValue($tokens, ++$tokenIndex);
//                } else {
//                    $result .= $item->getTokensValue($tokens, ++$tokenIndex);
//                }
//
//                self::$instancesCounter--;
                break;


            case 'tree':

                $item = $this->getClassForToken($currentToken, $nextToken);

                // La diferencia amb l'anterior es que no s'afegeix el pushState aquí, es gestionat per les classes
                $result .= $item->open();

//                $currentToken['instruction'] = $item;


                break;




            case 'self-contained':
//                die("self");
                // Aquest tipus no s'afegeix a l'stack perque s'auto tanca
                $item = $this->getClassForToken($currentToken, $nextToken);
                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);
                $result .= $item->open();
                $result .= $item->close();
//                $result = $item->getContent($currentToken);
                $this->popState();
                break;

//            case 'container':
//
//                $item = $this->getClassForToken($currentToken, $nextToken);
//                $class = static::$parserClass;
//
//                $currentToken['instruction'] = $item;
//                $this->pushState($currentToken);
//
//                $content = $item->getContent($currentToken);
//
//
//
//                $value = $class::getValue($content);
//                $result = $item->resolveOnClose($value);
//                $this->popState();
//
//                break;



            // El tancament pot correspondre a una marca de tancament o a l'apertura d'altre etiequeta, per tant
            // no es controla aquí, es comprova abans de parsejar amb les crides a "isClose()"
//            case 'close':
//                $this->popState();
////                return null;
//                break;
//            default:
//                die ($action . ' unimplemented');
        }


        // ALERTA: Això es necesari perque \n és un token de tancament però cal conservar-lo

        return $result;
    }

    public function parseTokens($tokens, &$tokenIndex = 0) {

        $result = '';

        while ($tokenIndex < count($tokens)) {

            $newChunk = $this->parseToken($tokens, $tokenIndex);

//            if ($newChunk === NULL) { // tancament de la etiqueta
//                break;
//            }

//            echo $tokenIndex . "/" . count($tokens) . "\n" ;

            ++$tokenIndex;
            $result .= $newChunk;

        }

        // Fi del bloc parsejat, tanquem totes les etiquetes pendents <-- ALERTA! Només si és el fi del document

        $class = static::$parserClass;

        if (!$class::isInner()) {

            // Això tanca totes les etiquetes obertes però no és correcte, no podem cridar tampoc al isClosing perque no sabem quin és el següent token
            while ($top = end(static::$stack)) {
                $result .= $top['instruction']->close();
                $this->popState();
            }
        }


        return $result;
    }
}
