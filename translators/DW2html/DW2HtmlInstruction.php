<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlInstruction extends IocInstruction {

    protected static $parserClass = "DW2HtmlParser";
    //protected static $defaultContentclass = "DW2HtmlContent";

    protected function resolveOnClose($result, $tokenEnd) {

        die("Aquest tipus d'instrucció no fa servir resolveOnClose");
    }

    public function open() {

        return $this->getReplacement(self::OPEN);
    }

    public function close() {
        return $this->getReplacement(self::CLOSE);
    }


    public function isClosing($token) {

//        var_dump($token);

        // Això es pot cridar quan es un parse directe de content
        return false;
//        die("Unimplemented");
    }

    public function parseToken($tokens, &$tokenIndex) {



        $currentToken = $tokens[$tokenIndex];
        $nextToken = $tokenIndex + 1 < count($tokens) ? $tokens[$tokenIndex + 1] : NULL;
        $result = '';

        if ($currentToken['state'] == 'content') {
            $action = 'content';
            $currentToken['class'] = static::$defaultContentclass;

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

        // Això provoca salts adicionals, revisar si ara funciona

        // Sense això no funciona, però amb això s'afegeix un salt de línia adicional davant de

        // TEST: Afegir spans adicionals al content
        $topIndex = count(WiocclParser::$structureStack)-1;
        if ($topIndex >= 0 && WiocclParser::$structureStack[$topIndex]>0) {
            $refId = WiocclParser::$structureStack[count(WiocclParser::$structureStack) - 1];
        } else {
            $refId = -1;
        }


        if ($currentToken['raw'] == "\n" ) {

            if ($refId !== -1) {
                $result .= '<span data-wioccl-ref="' . $refId . '">' .  $currentToken['raw'] . '</span>';
            } else if (!$top || ($top && $top['state'] !== 'paragraph')) {
                $result .= $currentToken['raw'];
            }
        }



        // Alerta, això de tancar automàticament és necessari per les llistes amb mùltiples nivells
        // Detectat problema només amb el <readonly></readonly> quan es embolcallat per altre readonly, afegit com a cas especial
        while ($top && $top['instruction']->isClosing($currentToken)) {

            $result .= $top['instruction']->Close();
            $this->popState();
            $extra = $top['extra'];
            $top = end(static::$stack);

            if ($extra && $extra['inline-block']) {
                break;
            }


        }

        $class = static::$parserClass;

        // Aquest cas es dona quan una línia comença per una etiqueta de tipus inline (no és block)
        if (!$class::isInner() && !$top && isset($currentToken['extra']) && $currentToken['extra']['block'] !== TRUE &&
                $currentToken['extra']['inline-block'] !== TRUE  && $currentToken['action'] !== 'close') {

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

                // PROBLEMA: si $inline == true no s'afegeixen els paragraphs a la edicio parcial
                // pendent de determinar en quin cas era necessari
                // if ((!$top || $top['state'] == 'newcontent') && !DW2HtmlParser::isInline()) {
            if ((!$top || $top['state'] == 'newcontent')) {

                    $newContainerToken = DW2HtmlParser::$defaultContainer;
                    $container = $this->getClassForToken($newContainerToken, $nextToken);
                    $newContainerToken['instruction'] = $container;
                    $this->pushState($newContainerToken);

                    // TEST: Afegir spans adicionals al content
//                    $result .= '<span data-test="**">' . $container->open() .'</span>';

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

                    // TEST: Afegir spans adicionals al content
                    $result .= $currentToken['value'];
//                    $result .= '<span data-test="**">' . $currentToken['value'] . '</span>';
                } else {

                    // ALERTA! Aquest és l'original
//                    $result .= $item->getContent($currentToken);

                    // TEST: Afegir spans adicionals al content
//                    $topIndex = count(WiocclParser::$structureStack)-1;

                    // El element amb id === 0 és el root, no s'afegeix
//                    if ($topIndex >= 0 && WiocclParser::$structureStack[$topIndex]>0) {
                    if ($refId !== -1) {
                        $refId = WiocclParser::$structureStack[count(WiocclParser::$structureStack)-1];
                        $result .= '<span data-wioccl-ref="'. $refId.'">'. $item->getContent($currentToken) . '</span>';
                    } else {
                        $result .= $item->getContent($currentToken);
                    }



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

        Logger::debug("\n### DW2HTML TOKENS START ###\n" . json_encode($tokens) . "\n### DW2HTML TOKENS END ###\n", 0, __LINE__, basename(__FILE__), 1, true);

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

            while ($top = end(static::$stack)) {
                $result .= $top['instruction']->close();
                $this->popState();
            }
        }


        return $result;
    }

    protected function parseContent($raw) {
        $class = static::$parserClass;
        $isInnerPrevious = $class::isInner();
        $class::setInner(true);

        $content = $class::getValue($raw);

        $class::setInner($isInnerPrevious);

//        echo '<pre>' . $content . '</pre>';
//        die();

        return $content;
    }

    public static function parseContent2($raw, $inline) {
        $class = static::$parserClass;
        $isInnerPrevious = $class::isInner();
        $class::setInner(true);

        $previousInline = $class::isInline();
        $class::setInline($inline);
        $content = $class::getValue($raw);

        // ALERTA! Hi ha un cas en que el html retornat no és correcte:
        // Es un cas molt concret i dificil de generalitzar, per això ho gestionem aquí mitjançant regex
        // cas: si es tracta d'una conversió parcial wioccl d'elements dins d'una taula generada amb foreach

        $pattern = "/^<span data-wioccl-ref=.*? data-wioccl-state='open'><\/span>(<tr.*)/";

        if (preg_match($pattern, $content, $matches)) {

            $content = $matches[1];
        }



        $class::setInline($previousInline);

        $class::setInner($isInnerPrevious);

//        echo '<pre>' . $content . '</pre>';
//        die();

        return $content;
    }

}
