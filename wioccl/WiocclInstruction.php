<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

//require_once DOKU_INC.'lib/lib_ioc/wioccl/WiocclStructureItem.php';


class WiocclInstruction extends IocInstruction {
    const FROM_CASE = "fromCase";
    const FROM_RESET = "fromReset";
    const FROM_REPARSESET = "fromReparseset";

    protected $rawValue;
    protected $fullInstruction = "";
    protected $parentInstruction = NULL;
    protected $updatablePrefix = "";

    protected $dataSource = [];

    protected $arrays = [];

    protected $resetables = null;

    protected static $parserClass = "WiocclParser";

    protected $item;
    protected $previousStructureGeneration = false;

    protected $skipOpening;

    // TODO: Afegir dataSource al constructor, deixem els arrays separats perque el seu us es intern, al datasource es ficaran com a JSON
    public function __construct($value = null, $arrays = array(), $dataSource = array(), &$resetables = NULL, &$parentInstruction = NULL, $skipOpening = false) {
        $this->rawValue = $value;
        $this->arrays += $arrays;
        $this->dataSource = $dataSource;
        $this->parentInstruction = $parentInstruction;
        if ($resetables == NULL) {
            $this->resetables = new WiocclResetableData();
        } else {
            $this->resetables = $resetables;
        }

        $this->skipOpening = $skipOpening;

        if (!$skipOpening) {
            $this->open();
        }


    }

    protected function open() {
        $class = (static::$parserClass);
        $this->item = new WiocclStructureItem($class::getStructure());

//        $this->item->rawValue = $value;

        $class::openItem($this->item);
    }

    public function updateParentArray($fromType, $key = NULL) {
        self::stc_updateParentArray($this, $fromType, $key);
    }

    public static function stc_updateParentArray(&$obj, $fromType, $key = NULL) {
        if ($obj->parentInstruction != NULL) {
            if ($key === NULL) {
                $obj->parentInstruction->arrays = array_merge($obj->parentInstruction->arrays, $obj->arrays);
            } else if (isset ($obj->arrays[$key])) {
                $obj->parentInstruction->arrays[$key] = $obj->arrays[$key];
            } else if (isset($obj->parentInstruction->arrays[$key])) {
                unset($obj->parentInstruction->arrays[$key]);
            }
            $obj->parentInstruction->updateParentArray($fromType, $key);
        }
    }

    protected function isClosingTagExcluded($type) {
        $class = static::$parserClass;
        return in_array($type, $class::getExcludedClosingTags());
    }


    public function parseToken($tokens, &$tokenIndex) {

        $currentToken = $tokens[$tokenIndex];
        $this->tokens = &$tokens;
        $currentToken['tokenIndex'] = $tokenIndex;


        $nextToken = $tokenIndex + 1 < count($tokens) ? $tokens[$tokenIndex + 1] : NULL;
        $result = '';

        if ($currentToken['state'] == 'content') {
            $action = 'content';
            $currentToken['class'] = static::$defaultContentclass;

        } else {
            $action = $currentToken['action'];
        }

        if ($action == 'open-close') {
            // Si l'ultim element del stack es del mateix tipus el tanca
            $top = end(static::$stack);

            if (count(static::$stack) > 0 && $top['state'] == $currentToken['state'] && $top['type'] == $currentToken['type']) {
                $action = 'close';
            } else {
                $action = 'open';
            }
        }

        switch ($action) {
            case 'content':


                // Si el parent d'aquest element és un field, llavors aquest és el nom del field i no content
                $top = $this->getTopState();
                $addToStructure = $top['type'] !== "field";

                $item = $this->getClassForToken($currentToken, $nextToken);

                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);

                // ALERTA: Els salts de línia s'afegeixen directament, sense processar


                if ($currentToken['value'] == "\n") {
                    $auxResult = $currentToken['value'];
                } else {

                    $auxResult = $item->getContent($currentToken);
                }


                if ($addToStructure) {

                    $this->addToStructure($auxResult, 'content', $currentToken['tokenIndex'], 'content');
                }

                $result .= $auxResult;
                $this->popState();






                break;

            case 'open':
                $mark = static::$instancesCounter == 0;
                static::$instancesCounter++;
                $item = $this->getClassForToken($currentToken, $nextToken);

                $currentToken['instruction'] = $item;


                if (!$currentToken['extra'] || !isset($currentToken['extra']['exclude-stack']) || !$currentToken['extra']['exclude-stack']) {
                    $this->pushState($currentToken);
                } else {
                    // no afegim a l'stack
                }


                if ($mark) {
                    ++$tokenIndex;
                    $result .= $item->getTokensValue($tokens, $tokenIndex);
                } else {
                    ++$tokenIndex;
                    $result .= $item->getTokensValue($tokens, $tokenIndex);
                }
                static::$instancesCounter--;
                break;

            case 'self-contained':
                // Aquest tipus no s'afegeix a l'stack perque s'auto tanca
                $item = $this->getClassForToken($currentToken, $nextToken);
                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);
                $result = $item->resolveOnClose($item->getContent($currentToken), $currentToken);
                $this->popState();
                break;

            case 'container':
                $item = $this->getClassForToken($currentToken, $nextToken);
                $class = static::$parserClass;

                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);

                $content = $item->getContent($currentToken);
                $value = $class::getValue($content);
                $result = $item->resolveOnClose($value);
                $this->popState();
                break;

            case 'close':
                $top = $this->getTopState();


                if ($currentToken['extra'] && isset($currentToken['extra']['exclude-stack']) && $currentToken['extra']['exclude-stack']) {
                    $isExcluded = true;
                    $top = true; // Alerta! això normalment conté un element d'un array, però en aquest cas només ens cal passar-lo com a true per no comprovar el tancament
                } else {
                    // ALERTA[Xavi]: el for/foreach no es pot tancar aquí perquè la etiqueta de tancament es processa a cada iteració
                    $isExcluded = $this->isClosingTagExcluded($currentToken['type']);
                }

                if (!$top || (!$isExcluded && $top['type'] !== $currentToken['type'])) {
                    // Variables per testeig, per comprovar quina es la causa de l'error
                    $noHiHaTop = !$top;
                    $noEsDelTipus = $top['type'] !== $currentToken['type'];
                    $noEsDelTipusYNoEsExcluded = $top['type'] !== $currentToken['type'] && !$isExcluded;

                    throw new WrongClosingTranslatorException([htmlspecialchars($top['value']), htmlspecialchars($currentToken['value'])]);
                }
                if (!$isExcluded) {
                    $this->popState();
                }
                return null;
            //break;
        }

        if (static::$instancesCounter === 0 && $action !== 'content') {
            $top = $this->getTopState();
            if ($top) {
                var_dump($top, $result);
                throw new MissingClosingTranslatorException(htmlspecialchars($top['value']));
            }
        }

        return $result;
    }


    // Aquest mètode afegeix un element a la estructura sense modificar les propietats de la instrucció actual
//    protected function addToStructure(&$result, $type, $startIndex = 0, $endIndex = 0, $trailing = false) {
    protected function addToStructure(&$result, $type, $startIndex = 0, $endIndex = 0) {


        if ($this->skipOpening) {
            return;
        }

        $class = (static::$parserClass);
        $item = new WiocclStructureItem($class::getStructure());

        $item->type = $type;
        $class::openItem($item);


        //$item->type = $type;
        // No hi ha etiquetas, el resultat és el contingut sense modificar


        // ALERTA[Xavi] el parser dw2html interpreta els \n com a generic close
        // i els \n+ com paragraph, mentre que el content sempre acaba sense salt,
        // en canvi el wioccl captura els \n com a part del contingut.

        // TODO: Eliminar, això no soluciona el problema i a més a més trenca moltes coses
//        if ($type === 'content' && substr($result, -1) ==="\n") {
//            // COMPTE! això dona problemas amb múltiples casos, només el token 82
//            // ha d'aplicar aquest comportament, quina és la diferencia? que es troba dintre d'un bloc wioccl?
//            $item->open = rtrim($result);
//        } else {
//            $item->open = $result;
//        }

        $item->open = $result;


        $class::closeItem();


        if ($class::$generateStructure && $item->id>=0) {
            $result = '[ref=' . $item->id . ']' . $result . '[/ref=' . $item->id . ']';
        }

        // TODO: eliminar d'aqui i de la crida, s'afegeix quan es tanca
//        if ($trailing) {
//            $result .= "\n";
//        }


        if ($class::$debugStructure) {
            $item->result = $result;
        }



    }


    protected function resolveOnClose($result, $tokenEnd) {
        // Implementació per defecte

        // ALERTA! per aquí només passen els generics, cal implementar això a tots els @override

        $this->close($result, $tokenEnd);

        return parent::resolveOnClose($result, $tokenEnd);
    }

    // [Xavi] per implementar a les subclasses, veure WiocclIf
    protected function isTrailingNeeded($token) {
        if ($this->startsWith($token['value'], "</WIOCCL") && substr($token['value'], -1) == "\n") {
            return true;
        }
        return false;
    }

    private function startsWith($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }

    protected function close(&$result, $tokenEnd) {

        if ($this->skipOpening) {
            return;
        }

        if ($this->item->id>=0) {

            $trailing = $this->isTrailingNeeded($tokenEnd);

            $result = '[ref=' . $this->item->id . ']' . $result . '[/ref=' . $this->item->id . ']';

            if ($trailing) {
                $result .= "\n";
            }

            $this->item->type = $this->currentToken['type'];
        }


        $class = (static::$parserClass);
        $class::closeItem();

        if ($class::$debugStructure) {
            $this->item->result = $result;
        }

        $tag = $this->currentToken['value'];
        $attrs = "";

        $this->splitOpeningAttrs($tag, $attrs);


        if ($this->currentToken['extra'] && isset($this->currentToken['extra']['opening-format'])) {
            $this->item->open = $this->currentToken['extra']['format'];
        } else {
            $this->item->open = $tag;
        }

        $this->item->attrs = $attrs;

        if (!isset($tokenEnd['extra']) || !$tokenEnd['extra']['ignoreStructureClosing']) {
            $this->item->close = $tokenEnd['value'];
        }

        // Codi per afegir la estructura
        //$this->rebuildRawValue($this->item, $this->currentToken['tokenIndex'], $tokenEnd['tokenIndex']);

    }

    protected function splitOpeningAttrs(&$tag, &$attrs) {
        // La implementació més genèrica és considerar que tot el que estigui desprès del primer espai son atributs
        // quan això no és vàlid (per exemple a les funcions), es fa @override d'aquesta funció


        // ALERTA! substituim el \> per &gt; perquè si no dona problemas amb el > de tancament normal

        $tag = str_replace('\\>', '&escapedgt;', $tag);



        // Cerquem el primer element començant per la cua que no sigui ni un espai ni un salt de línia
        preg_match('/(>.*)$/ms', $tag, $match);
        $tail = $match[1];




//        $tag = substr($tag, 0, strlen($tag)-1);
        $tag = preg_replace('/(>.*)$/m', '', $tag);
//        $tag = preg_replace('/(>.*)$/m', '', $tag);


        $aux = explode(' ', $tag);

        if (count($aux)===0) {
            return;
        }

        $tag = $aux[0] . ' %s'. $tail;

        // Eliminem el primer element
        array_shift($aux);

        $attrs = trim(implode(" ", $aux));

    }


    protected function generateRawValue(&$value, $startIndex, $endIndex) {
        $value = "";
        for ($i = $startIndex; $i <= $endIndex; $i++) {
            $value.= $this->tokens[$i]['value'];
        }
    }

    public function pauseStructureGeneration() {

        $class = (static::$parserClass);
        $this->previousStructureGeneration = $class::$generateStructure;
        $class::$generateStructure = false;
    }

    public function resumeStructureGeneration() {
        $class = (static::$parserClass);
        $class::$generateStructure = $this->previousStructureGeneration;
    }
}
