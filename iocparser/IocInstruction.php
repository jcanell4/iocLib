<?php

class IocInstruction {

    const DEBUG_MODE = FALSE;
    const OPEN = 0;
    const CLOSE = 1;


    protected $extra;
    protected $rawValue;
    protected static $instancesCounter = 0;
    protected $arrays = [];

    protected static $parserClass = "IocParser";

    public static $stack = [];

    protected $currentToken;
    protected $nextToken;


    public function __construct($value = null, $arrays = array()/*, $dataSource = array(), &$resetables=NULL, &$parentInstruction=NULL*/) {
        $this->rawValue = $value;
        $this->arrays += $arrays;
    }

    protected function resolveOnClose($result) {
        return $result;
    }

    public function setTokens($currentToken, $nextToken) {
        $this->currentToken = $currentToken;
        $this->nextToken = $nextToken;
    }


    public function getTokensValue($tokens, &$tokenIndex) {
        return $this->parseTokens($tokens, $tokenIndex);
    }

    protected function getContent($token) {
//        var_dump($token['value']);
        return $token['value'];
    }

    public function parseTokens($tokens, &$tokenIndex = 0) {

        $result = '';

        while ($tokenIndex < count($tokens)) {

            $newChunk = $this->parseToken($tokens, $tokenIndex);

            if ($newChunk === NULL) { // tancament de la etiqueta
                break;
            }

            ++$tokenIndex;
            $result .= $newChunk;

        }




        return $this->resolveOnClose($result);
    }

    // l'index del token analitzat s'actualitza globalment per referència
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
                $mark = self::$instancesCounter == 0;
                self::$instancesCounter++;
                $item = $this->getClassForToken($currentToken, $nextToken);

                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);

                if ($mark) {
                    $result .= $item->getTokensValue($tokens, ++$tokenIndex);
                } else {
                    $result .= $item->getTokensValue($tokens, ++$tokenIndex);
                }

                self::$instancesCounter--;
                break;


            case 'self-contained':
                // Aquest tipus no s'afegeix a l'stack perque s'auto tanca
                $item = $this->getClassForToken($currentToken, $nextToken);
                $currentToken['instruction'] = $item;
                $this->pushState($currentToken);
                $result = $item->getContent($currentToken);
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

                // ALERTA[Xavi]: el for/foreach no es pot tancar aquí perquè la etiqueta de tancament es processa a cada iteració
                $isExcluded = $this->isClosingTagExcluded($currentToken['type']);

                if ( !$top || ($top['type'] !== $currentToken['type'] && !$isExcluded)) {
                    throw new WrongClosingTranslatorException([htmlspecialchars($top['value']), htmlspecialchars($currentToken['value'])]);
                }

                if (!$isExcluded) {
                    $this->popState();
                }

                return null;
                break;
        }


        if (self::$instancesCounter === 0) {
            $top = $this->getTopState();
            if ($top) {
                //var_dump($top, $result);
                throw new MissingClosingTranslatorException(htmlspecialchars($top['value']));
            }
        }



        return $result;
    }

    protected function isClosingTagExcluded($type) {
        return false;
    }

    protected function setExtra($extraData) {
        $this->extra = $extraData;
    }

    protected function getClassForToken($token, $next) {
        $instance = new $token['class']($token['value'], $this->getArrays(), $this->getDataSource(), $this->resetables, $this);
        $instance->setTokens($token, $next);
        $instance->setExtra($token['extra']);
        return $instance;
    }

    protected function normalizeArg($arg) {
        if (strtolower($arg) == 'true') {
            return true;
        } else if (strtolower($arg) == 'false') {
            return false;
        } else if (is_int($arg)) {
            return intval($arg);
        } else if (is_numeric($arg)) {
            return floatval($arg);
        } else if (preg_match("/^''(.*?)''$/", $arg, $matches) === 1) {
            return $this->normalizeArg($matches[1]);
        } else {
            return $arg;
        }

    }

    protected function extractNumber($value, $attr, $mandatory = true) {
        $ret = 0;
        if (preg_match('/' . $attr . '="(.*?)"/', $value, $matches)) {
            $class = static::$parserClass;
            $ret = $class::getValue($matches[1], $this->getArrays(), $this->getDataSource(), $this->getResetables());
        } else if ($mandatory) {
            throw new Exception("$attr is missing");
        }
        if (is_numeric($ret)) {
            $ret = intval($ret);
        }
        return $ret;
    }

    protected function extractVarName($value, $attr = "var", $mandatory = true) {
        if (preg_match('/' . $attr . '="(.*?)"/', $value, $matches)) {
            return $matches[1];
        } else if ($mandatory) {
            throw new Exception("$attr name is missing");
        }
        return "";
    }

    protected function extractArray($value, $attr = "array", $mandatory = true) {
        $jsonString = '[]';
        // ALERTA: El $value pot ser un json directament o una variable, s'ha de fer un parse del $value
        if (preg_match('/' . $attr . '="(.*?)"/', $value, $matches)) {
            $string = preg_replace("/''/", '"', $matches[1]);
            $class = static::$parserClass;
            $jsonString = $class::getValue($string, $this->getArrays(), $this->getDataSource(), $this->getResetables());
        } else if ($mandatory) {
            throw new Exception("Array is missing");
        }
        return json_decode($jsonString, true);
    }

    protected function extractMap($value, $attr = "map", $mandatory = true) {
        $jsonString = '{}';
        // ALERTA: El $value pot ser un json directament o una variable, s'ha de fer un parse del $value
        if (preg_match('/' . $attr . '="(.*?)"/', $value, $matches)) {
            $string = preg_replace("/''/", '"', $matches[1]);

            $class = static::$parserClass;
            $jsonString = $class::getValue($string, $this->getArrays(), $this->getDataSource(), $this->getResetables());
        } else if ($mandatory) {
            throw new Exception("Map is missing");
        }
        return json_decode($jsonString, true);
    }

    public function getResetables() {
        return $this->resetables;
    }

    public function getDataSource() {
        return $this->dataSource;
    }

    public function setArrayValue($key, $value) {
        $this->arrays[$key] = $value;
    }

    public function getArrays() {
        return $this->arrays;
    }

    public function getRawValue() {
        return $this->rawValue;
    }

    public function update($rightValue, $result = "") {
        throw new Exception("This class is not updatable");
    }

    public function pushState($token) {
        static::$stack[] = $token;
    }

    public function popState() {
        return array_pop(static::$stack);
    }

    public function getPreviousState() {
        return static::$stack > 1 ? static::$stack[count(static::$stack) - 2] : FALSE;
    }

    public function getTopState() {
        return end(static::$stack);
    }

    protected function getReplacement($position) {

        if (static::DEBUG_MODE) {
            return $this->getDebugReplacement($position);
        } else {
            return is_array($this->extra['replacement']) ? $this->extra['replacement'][$position] : $this->extra['replacement'];
        }
    }

    protected function getDebugReplacement($position) {
        $replacement = is_array($this->extra['replacement']) ? $this->extra['replacement'][$position] : $this->extra['replacement'];

        if ($position == self::OPEN) {
            $replacement = '<' . $this->currentToken['state'] . '>' . $replacement;
        } else {
            $replacement = $replacement . '</' . $this->currentToken['state'] . '>';
        }

        return $replacement;
    }

    public static function AddAttributeToTag($tag, $attribute, $value) {

        $addEndNewLine = false;

        if (substr($tag, -1) == "\n") {
            $tag = substr($tag, 0, strlen($tag) - 1);
            $addEndNewLine = true;
        }

        $newTag = substr($tag, 0, strlen($tag) - 1);

        if (substr($newTag, -1) !== " ") {
            $newTag .= " ";
        }

        $newTag .= $attribute . '="' . $value . '">';

        if ($addEndNewLine) {
            $newTag .= "\n";
        }

        return $newTag;
    }

}
