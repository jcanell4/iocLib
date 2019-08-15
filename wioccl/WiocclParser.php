<?php

class WiocclParser
{

    protected static $removeTokenPatterns = [
        '/:###/', '/###:/'
    ];

    protected static $tokenPatterns = [
        '{@@' => [
            'state' => 'open_extra',
        ],
        '@@}' => [
            'state' => 'close_extra',
        ],
        '{##' => [
            'state' => 'open_field',
        ],
        '##}' => [
            'state' => 'close_field',
        ],
        '{#_' => [
            'state' => 'open_function',
        ],
        '_#}' => [
            'state' => 'close_function',
        ],
        '{%%' => [
            'state' => 'open_lazyfield',
        ],
        '%%}' => [
            'state' => 'close_lazyfield',
        ],
        '<WIOCCL:IF .*?[^\\\\]>(\n)?' => [
            'state' => 'open_if',
        ],
        '</WIOCCL:IF>(\n)?' => [
            'state' => 'close_if',
        ],
        '<WIOCCL:FOREACH .*?[^\\\\]>(\n)?' => [
            'state' => 'open_foreach',
        ],
        '</WIOCCL:FOREACH>(\n)?' => [
            'state' => 'close_foreach',
        ],
        '<WIOCCL:FOR .*?>(\n)?' => [
            'state' => 'open_for',
        ],
        '</WIOCCL:FOR>(\n)?' => [
            'state' => 'close_for',
        ],
        '<WIOCCL:SUBSET .*?[^\\\\]>(\n)?' => [
            'state' => 'open_subset',
        ],
        '</WIOCCL:SUBSET>(\n)?' => [
            'state' => 'close_subset',
        ],
        '<WIOCCL:SET .*?[^\\\\]>(\n)?' => [
            'state' => 'open_set',
        ],
        '</WIOCCL:SET>(\n)?' => [
            'state' => 'close_set',
        ],
        '<WIOCCL:RESET .*?[^\\\\]>(\n)?' => [
            'state' => 'open_reset',
        ],
        '</WIOCCL:RESET>(\n)?' => [
            'state' => 'close_reset',
        ],
        '<WIOCCL:CHOOSE .*?[^\\\\]>(\n)?' => [
            'state' => 'open_choose',
        ],
        '</WIOCCL:CHOOSE>(\n)?' => [
            'state' => 'close_choose',
        ],
        '<WIOCCL:CASE .*?[^\\\\]>(\n)?' => [
            'state' => 'open_case',
        ],
        '</WIOCCL:CASE>(\n)?' => [
            'state' => 'close_case',
        ],
        '<WIOCCL:DEFAULTCASE.*?[^\\\\]>(\n)?' => [
            'state' => 'open_defaultcase',
        ],
        '</WIOCCL:DEFAULTCASE>(\n)?' => [
            'state' => 'close_defaultcase',
        ],
        '<WIOCCL:REPARSE>(\n)?' => [
            'state' => 'open_reparse',
        ],
        '</WIOCCL:REPARSE>(\n)?' => [
            'state' => 'close_reparse',
        ],
        '<WIOCCL:REPARSESET.*?[^\\\\]>(\n)?' => [
            'state' => 'open_reparseset',
        ],
        '</WIOCCL:REPARSESET>(\n)?' => [
            'state' => 'close_reparseset',
        ],
    ];

    // TODO: automatitzar la creació a partir del token patterns? <-- no seria posible en el cas del open del if
    // TODO: Determinar si es necessari coneixer el tipus o només cal l'state
    // Automatitzar la generació de noms de les classes a partir del wioccl:**
    protected static  $tokenKey = [
        '<WIOCCL:FOR' => ['state' => 'open_for', 'type' => 'for', 'class' => 'WiocclFor', 'action' => 'open'],
        '</WIOCCL:FOR>' => ['state' => 'close_for', 'type' => 'for', 'action' => 'close'],
        '<WIOCCL:FOREACH' => ['state' => 'open_foreach', 'type' => 'foreach', 'class' => 'WiocclForEach', 'action' => 'open'],
        '</WIOCCL:FOREACH>' => ['state' => 'close_foreach', 'type' => 'foreach', 'action' => 'close'],
        '<WIOCCL:IF' => ['state' => 'open_if', 'type' => 'if', 'class' => 'WiocclIf', 'action' => 'open'],
        '</WIOCCL:IF>' => ['state' => 'close_if', 'type' => 'if', 'action' => 'close'],
        '<WIOCCL:SUBSET' => ['state' => 'open_subset', 'type' => 'subset', 'class' => 'WiocclSubset', 'action' => 'open'],
        '</WIOCCL:SUBSET>' => ['state' => 'close_subset', 'type' => 'subset', 'action' => 'close'],
        '<WIOCCL:SET' => ['state' => 'open_set', 'type' => 'set', 'class' => 'WiocclSet', 'action' => 'open'],
        '</WIOCCL:SET>' => ['state' => 'close_set', 'type' => 'set', 'action' => 'close'],
        '{@@' => ['state' => 'open_extra', 'type' => 'extra', 'class' => 'WiocclExtra', 'action' => 'open'],
        '@@}' => ['state' => 'close_extra', 'type' => 'extra', 'action' => 'close'],
        '{##' => ['state' => 'open_field', 'type' => 'field', 'class' => 'WiocclField', 'action' => 'open'],
        '##}' => ['state' => 'close_field', 'type' => 'field', 'action' => 'close'],
        '{#_' => ['state' => 'open_function', 'type' => 'function', 'class' => 'WiocclFunction', 'action' => 'open'],
        '_#}' => ['state' => 'close_function', 'type' => 'function', 'action' => 'close'],
        '{%%' => ['state' => 'open_lazyfield', 'type' => 'lazyfield', 'class' => 'WiocclLazyField', 'action' => 'open'],
        '%%}' => ['state' => 'close_lazyfield', 'type' => 'lazyfield', 'action' => 'close'],
        '<WIOCCL:RESET' => ['state' => 'open_reset', 'type' => 'reset', 'class' => 'WiocclReSet', 'action' => 'open'],
        '</WIOCCL:RESET>' => ['state' => 'close_reset', 'type' => 'reset', 'action' => 'close'],
        '<WIOCCL:CHOOSE' => ['state' => 'open_choose', 'type' => 'choose', 'class' => 'WiocclChoose', 'action' => 'open'],
        '</WIOCCL:CHOOSE>' => ['state' => 'close_choose', 'type' => 'choose', 'action' => 'close'],
        '<WIOCCL:CASE' => ['state' => 'open_case', 'type' => 'case', 'class' => 'WiocclCase', 'action' => 'open'],
        '</WIOCCL:CASE>' => ['state' => 'close_case', 'type' => 'case', 'action' => 'close'],
        '<WIOCCL:DEFAULTCASE' => ['state' => 'open_case', 'type' => 'case', 'class' => 'WiocclDefaultCase', 'action' => 'open'],
        '</WIOCCL:DEFAULTCASE>' => ['state' => 'close_case', 'type' => 'case', 'action' => 'close'],
        '<WIOCCL:REPARSE>' => ['state' => 'open_reparse', 'type' => 'reparse', 'class' => 'WiocclReparse', 'action' => 'open'],
        '</WIOCCL:REPARSE>' => ['state' => 'close_reparse', 'type' => 'reparse', 'action' => 'close'],
        '<WIOCCL:REPARSESET' => ['state' => 'open_reparseset', 'type' => 'reparseset', 'class' => 'WiocclReparseSet', 'action' => 'open'],
        '</WIOCCL:REPARSESET>' => ['state' => 'close_reparseset', 'type' => 'reparseset', 'action' => 'close'],
    ];

    public static function getValue($text = null, $arrays = [], $dataSource = [])
    {
        $replacements = array_fill(0, count(self::$removeTokenPatterns), '');

        $text = preg_replace(self::$removeTokenPatterns, $replacements, $text);

        return self::parse($text, $arrays, $dataSource);
    }

    public static function parse($text = null, $arrays = [], $dataSource = [])
    {
        $instruction = new WiocclInstruction($text, $arrays, $dataSource);
        $tokens = self::tokenize($instruction->getRawValue()); // això ha de retornar els tokens
        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
    }


    protected static function tokenize($rawText)
    {

        // Creem la regexp que permet dividir el $text
        $pattern = '(';

        foreach (self::$tokenPatterns as $statePattern => $data) {
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

            $tokens[] = self::generateToken($match[0]);

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


        foreach (self::$tokenKey as $key => $value) {

            if (strpos($tokenInfo, $key) === 0) {
                // It starts with the token
                $token['state'] = $value['state'];
                $token['class'] = isset($value['class']) ? $value['class'] : null;
                $token['action'] = $value['action'];
            }
        }

        return $token;
    }
}
