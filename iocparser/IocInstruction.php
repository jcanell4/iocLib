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

    protected static $stack = [];

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

//            var_dump($newChunk);



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

                // ALERTA: Els salts de línia s'afegeixen directament, sense processar
                if ($currentToken['value'] == "\n") {
                    $result .= $currentToken['value'];
                } else {
                    $result .= $this->getContent($currentToken);
                }


//                var_dump($this->getContent($currentToken));


                break;


            case 'open':
                $this->pushState($currentToken);
                $mark = self::$instancesCounter == 0;
                self::$instancesCounter++;
                $item = $this->getClassForToken($currentToken, $nextToken);

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
                $result = $item->getContent($currentToken);
                break;

            case 'container':
                // Aquest tipus no s'afegeix a l'stack perque resol el seu propi contingut
                $item = $this->getClassForToken($currentToken, $nextToken);
                $class = static::$parserClass;
                $result = $item->resolveOnClose($class::getValue($item->getContent($currentToken)));
                break;

            case 'close':
                $this->popState();
                return null;
                break;
        }

        return $result;
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
        array_pop(static::$stack);
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
            $replacement = '<' . $this->currentToken['state'] . '>'.$replacement;
        } else {
            $replacement = $replacement . '</' . $this->currentToken['state'] . '>';
        }

        return $replacement;
    }


}
