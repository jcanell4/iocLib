<?php

class IocParser {

//    protected static $isContainer = FALSE;

    protected static $removeTokenPatterns = [];

    protected static $tokenPatterns = [];

    public static $forceReplacements = [];

    // Aquests tipus de bloc no es tanquen automàticament
    protected static $excludedClosingTags = [];


    // ALERTA! La key es un string, no una expresió regular
    protected static $tokenKey = [];

    protected static $instructionClass = "IocInstruction";

    public static $isInner = false;

    public static function isInner() {
        return static::$isInner;
    }

    public static function setInner($value) {
        static::$isInner = $value;
    }

    public static function getValue($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {

        $replacements = array_fill(0, count(static::$removeTokenPatterns), '');

        $text = preg_replace(static::$removeTokenPatterns, $replacements, $text);

        return static::parse($text, $arrays, $dataSource, $resetables);
    }

    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables = NULL) {

        $instruction = new static::$instructionClass($text, $arrays, $dataSource, $resetables);
        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens

//        for ($i = 0; $i < count($tokens); $i++) {
////            var_dump($tokens[$i]['raw']);
//            var_dump($tokens[$i]);
////            die();
//        }

//        if (static::$isInner) {
//            var_dump($tokens);
//        }

//        var_dump($tokens[0], $tokens[1], $tokens[3]);
//        die();

        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats

    }

    protected static function getPattern() {
        $pattern = '(';

        foreach (static::$tokenPatterns as $statePattern => $data) {
            $pattern .= $statePattern . '|';
        }

        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')';

//        var_dump($pattern);
        return $pattern;
    }

    protected static function tokenize($rawText) {
        $pattern = static::getPattern();
        preg_match_all($pattern, $rawText, $matches, PREG_OFFSET_CAPTURE);

        // A $matches s'han de trobar totes les coincidencies de la expressió amb la posició de manera que podem extra polar el contingut "pla" que no forma part dels tokens

        $tokens = [];

        $pos = 0;

        for ($i = 0; $i < count($matches[0]); $i++) {
            $match = $matches[0][$i];
            $len = strlen($match[0]);
            $text = substr($rawText, $pos, $match[1] - $pos);


            // la posició inicial es igual a la posició final del token anterior? <-- s'ha trobat content
            if ($pos !== $match[1]) {


                $candidateToken = static::generateToken($match[0]);
                if ($pos == 0 && $candidateToken['state'] == 'none') {
                    $token = $candidateToken;
                    $token['value'] = $text;
                } else {
                    $token = ['state' => 'content', 'value' => $text];
                }

                $tokens[] = $token;

            }

            $token = static::generateToken($match[0]);

            $tokens[] = $token;
            $pos = $match[1] + $len;
        }

        if ($pos < strlen($rawText)) {
            $tokens[] = ['state' => 'content', 'value' => substr($rawText, $pos, strlen($rawText) - $pos)];
        }

//        var_dump($tokens);

        return $tokens;

    }

    protected static function generateToken($tokenInfo) {
        $token = ['state' => 'none', 'class' => null, 'value' => $tokenInfo];
        $pattern = null;

        foreach (static::$tokenKey as $key => $value) {

            $mustBeExact = isset($value['extra']) && $value['extra']['exact'] === TRUE;
            $isRegex = isset($value['extra']) && $value['extra']['regex'] === TRUE;

            $pattern = '/' . $key. '/ms';

            if (($mustBeExact && $tokenInfo == $key) || (!$mustBeExact && strpos($tokenInfo, $key) === 0) ||
                $isRegex && preg_match($pattern, $tokenInfo)) {
                $token = $value;
                break;
            }

        }

        $token['raw'] = $tokenInfo;
        $token['pattern'] = $pattern;

        return $token;
    }

    static public function getExcludedClosingTags() {
        return static::$excludedClosingTags;
    }
}
