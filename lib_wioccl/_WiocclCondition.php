<?php
require_once "WiocclParser.php";

class _WiocclCondition
{

    protected $arg1;
    protected $arg2;
    protected $operator;

    protected $source;

    public function __construct($value, $source)
    {
        $this->source = $source;

        $this->extractArgs($value);
    }


    public function validate()
    {

        $this->parseArgs($arg1, $arg2);

        return $this->resolveCondition($arg1, $arg2, $this->operator);
    }

    protected function extractArgs($value)
    {
        if (preg_match('/(?:condition|filter)\s*=\s*"(.*?)([><=!]=?)(.*?)"/', $value, $matches) > 0) {
            $this->arg1 = $matches[1];
            $this->arg2 = $matches[3];
            $this->operator = $matches[2];
        };

    }

    protected function parseArgs(&$arg1, &$arg2)
    {
        $arg1 = self::normalizeArg((new WiocclParser($this->arg1, $this->source->arrays, $this->source->dataSource))->getValue());
        $arg2 = self::normalizeArg((new WiocclParser($this->arg2, $this->source->arrays, $this->source->dataSource))->getValue());
    }

    protected function resolveCondition($arg1, $arg2, $operator)
    {

        if ($arg1 === null || $arg2 === null || $operator === null) {
            return false;
        }

        switch ($operator) {

            case '==':
                return $arg1 == $arg2;
            case '<=':
                return $arg1 <= $arg2;
            case '<':
                return $arg1 < $arg2;
            case '>=':
                return $arg1 >= $arg2;
            case '>':
                return $arg1 > $arg2;
            case '!=':
                return $arg1 != $arg2;
            default:
                return $arg1 && $arg2;
        }

    }

    protected function normalizeArg($arg)
    {
        if (strtolower($arg) == 'true') {
            return true;
        } else if (strtolower($arg) == 'false') {
            return false;
        } else if (is_int($arg)) {
            return intval($arg);
        } else if (is_numeric($arg)) {
            return floatval($arg);
        } else if (preg_match("/^'(.*?)'$/", $arg, $matches) === 1) {
            return $this->normalizeArg($matches[1]);
        } else {
            return $arg;
        }

    }
}