<?php

class IocParser
{

    protected static $removeTokenPatterns = [];

    protected static $tokenPatterns = [];

    protected static  $tokenKey = [];

    protected static $instructionClass = "IocInstruction";

    public static function getValue($text = null, $arrays = [], $dataSource = [], &$resetables=NULL)
    {
        $replacements = array_fill(0, count(self::$removeTokenPatterns), '');

        $text = preg_replace(self::$removeTokenPatterns, $replacements, $text);

        return self::parse($text, $arrays, $dataSource, $resetables);
    }

    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables=NULL)
    {

        $instruction = new static::$instructionClass($text, $arrays, $dataSource, $resetables);
        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens


        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
    }


    protected static function tokenize($rawText)
    {

        // Creem la regexp que permet dividir el $text
        $pattern = '(';

        foreach (static::$tokenPatterns as $statePattern => $data) {
            $pattern .= $statePattern . '|';
        }

        $pattern = substr($pattern, 0, strlen($pattern) - 1) . ')';

        preg_match_all($pattern, $rawText, $matches, PREG_OFFSET_CAPTURE);

        // A $matches s'han de trobar totes les coincidencies de la expressió amb la posició de manera que podem extra polar el contingut "pla" que no forma part dels tokens

        $tokens = [];

        $pos = 0;

        for ($i = 0; $i < count($matches[0]); $i++) {
            $match = $matches[0][$i];

            $len = strlen($match[0]);

            // la posició inicial es igual a la posició final del token anterior? <-- s'ha trobat content
            if ($pos !== $match[1]) {
                $text = substr($rawText, $pos, $match[1] - $pos);
                $tokens[] = ['state' => 'content', 'value' => $text];
            }

            $tokens[] = static::generateToken($match[0]);

            $pos = $match[1] + $len;
        }

        if ($pos < strlen($rawText)) {
            $tokens[] = ['state' => 'content', 'value' => substr($rawText, $pos, strlen($rawText) - $pos)];
        }


        return $tokens;

    }

    protected static function generateToken($tokenInfo)
    {
        $token = ['state' => 'none', 'class' => null, 'value' => $tokenInfo];


        foreach (static::$tokenKey as $key => $value) {

            if (strpos($tokenInfo, $key) === 0) {
                // It starts with the token
                $token['state'] = $value['state'];
                $token['class'] = isset($value['class']) ? $value['class'] : null;
                $token['action'] = $value['action'];
                $token['extra'] = $value['extra'];
            }
        }

        return $token;
    }
}
