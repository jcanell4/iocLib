<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlInstruction extends IocInstruction {

    protected static $parserClass = "DW2HtmlParser";

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
                $action = 'close';
            } else {
                $action = 'open';
            }

        }

        while ($top && $top['instruction']->isClosing($currentToken)) {

            var_dump($top);
            echo "TANCANT\n";

            $result .= $top['instruction']->Close();

            $this->popState();

            $top = end(static::$stack);
            //var_dump($result);
        }



        var_dump($currentToken);

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


            case 'self-contained':
                // Aquest tipus no s'afegeix a l'stack perque s'auto tanca
                $item = $this->getClassForToken($currentToken, $nextToken);
                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);
                $result = $item->getContent($currentToken);
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
            // no es controla aquí
//            case 'close':
//                $this->popState();
////                return null;
//                break;
//            default:
//                die ($action . ' unimplemented');
        }


        // ALERTA: Això es necesari perque \n és un token de tancament però cal conservar-lo
        if ($currentToken['raw'] == "\n") {
            $result .=$currentToken['raw'];
        }

        return $result;
    }

    public function parseTokens($tokens, &$tokenIndex = 0) {

        $result = '';

        while ($tokenIndex < count($tokens)) {

            $newChunk = $this->parseToken($tokens, $tokenIndex);

//            if ($newChunk === NULL) { // tancament de la etiqueta
//                break;
//            }

            echo $tokenIndex . "/" . count($tokens) . "\n" ;

            ++$tokenIndex;
            $result .= $newChunk;

        }

        // Fi del bloc parsejat, tanquem totes les etiquetes pendents
        while ($top = end(static::$stack)) {
            $result .= $top['instruction']->close();
            $this->popState();
        }


        return $result;
    }
}
