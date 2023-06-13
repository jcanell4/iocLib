<?php

abstract class abstractResolveValues {

    protected static $result = [];

    public static function setResult($result) {
        self::$result[] = $result;
    }

    public static function getResult() {
        return self::$result;
    }

}

class ResolveValues extends abstractResolveValues {
    
    private $values;

    public function __construct() {
        $this->values = [
            rslvExtractString::$className,
            rslvResolveFunction::$className,
        ];
    }

    public static function resolve($param, $full_param) {
        while ($param) {
            foreach ($this->values as $value) {
                if (call_user_func([$value, 'match'], $param)) {
                    $result = (new $value($this))->getValue($param);
                    $param = $result[0];
                    if (isset($result[1])) {
                        self::setResult($result[1]);
                    }
                }
            }
        }
    }

}

class rslvResolveFunction {

    public static $className = "rslvResolveFunction";
    protected static $pattern = '/([A-Z]+\(.*\))(?=,|$)/';

    public static function match($param) {
        return (bool)(preg_match("/[^A-Z]/", $param[0]));
    }

    public static function getValue($param) {
        $result = [];
            preg_match(self::$pattern, $param, $match);
            $result[] = preg_replace("/${match[0]}[,\s]*/", "", $param, 1);
            $result[] = $match[0];
        return $result;
    }

}

class rslvExtractString {

    public static $className = "rslvExtractString";
    protected static $pattern = '/(".*?")/';

    public static function match($param) {
        return (bool)($param[0] == '"');
    }

    public static function getValue($param) {
        $result = [];
        if ($param[0] == '"') {
            preg_match(self::$pattern, $param, $match);
            $result[] = preg_replace("/${match[0]}[,\s]*/", "", $param, 1);
            $result[] = $match[0];
        }else {
            $result[] = $param;
        }
        return $result;
    }

}

class ListParserValues {

    private $values;

    public function __construct() {
        $this->values = [
            rslvExtractString::$className,
        ];
    }

    public function parse($text = null, $arrays = [], $dataSource = []) {
        foreach ($this->values as $value) {
            if (call_user_func([$value, 'match'], $text)) {
                return (new $value($this))->getValue($text, $arrays, $dataSource);
            }
        }
        return null;
    }

}