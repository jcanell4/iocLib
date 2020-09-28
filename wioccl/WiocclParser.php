<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC.'lib/lib_ioc/iocparser/IocParser.php';

class WiocclParser extends IocParser
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
        '<WIOCCL:CONDSET .*?[^\\\\]>(\n)?' => [
            'state' => 'open_conditionalset',
        ],
        '</WIOCCL:CONDSET>(\n)?' => [
            'state' => 'close_conditionalsset',
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

    protected static $excludedClosingTags = [
        'for', 'foreach'
    ];

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
        '<WIOCCL:CONDSET' => ['state' => 'open_conditionalset', 'type' => 'conditionalset', 'class' => 'WiocclConditionalSet', 'action' => 'open'],
        '</WIOCCL:CONDSET>' => ['state' => 'close_conditionalset', 'type' => 'conditionalset', 'action' => 'close'],
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

    protected static $instructionClass = "WiocclInstruction";

    public static function getValue($text = null, $arrays = [], $dataSource = [], &$resetables=NULL)
    {
        $replacements = array_fill(0, count(static::$removeTokenPatterns), '');

        $text = preg_replace(static::$removeTokenPatterns, $replacements, $text);

        return static::parse($text, $arrays, $dataSource, $resetables);
    }

    public static function parse($text = null, $arrays = [], $dataSource = [], &$resetables=NULL)
    {
        $instruction = new static::$instructionClass($text, $arrays, $dataSource, $resetables);
        $tokens = static::tokenize($instruction->getRawValue()); // això ha de retornar els tokens
        return $instruction->parseTokens($tokens); // això retorna un únic valor amb els valor dels tokens concatenats
    }



    // @override aquesta versió es més simple que la del IocParser, es la original, no cal res més
    protected static function generateToken($tokenInfo)
    {
        $token = ['state' => 'none', 'class' => null, 'value' => $tokenInfo];


        foreach (static::$tokenKey as $key => $value) {

            if (strpos($tokenInfo, $key) === 0) {
                // It starts with the token
                $token['state'] = $value['state'];
                $token['type'] = $value['type'];
                $token['class'] = isset($value['class']) ? $value['class'] : null;
                $token['action'] = $value['action'];
                $token['extra'] = $value['extra'];
            }
        }

        return $token;
    }
}
