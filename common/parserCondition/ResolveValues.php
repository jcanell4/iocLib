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

class analysisResolver {

//    private $af = [];       //array de noms de funcions
//    private $nf = 0;        //nivell de la funció actual
//    private $prnts = 0;     //nivell de paréntesis
//    private $ap = [];       //array de paràmetres
//    private $np = 0;        //número de paràmetre de la funció actual
//    private $ic = false;    //inicio comillas

    public function __construct() {

    }

    public function analysis($sentence) {
        $af = [];       //array de noms de funcions
        $nf = 0;        //nivell de la funció actual (nivell de paréntesis)
        $ap = [];       //array de paràmetres
        $np = 0;        //número de paràmetre de la funció actual
        $ic = false;    //inicio comillas

        for ($i = 0; $i < strlen($sentence); $i++) {
            switch ($sentence[$i]) {
                case '(':
                    $nf++;
                    break;
                case ')':
                    $nf--;
                    break;
                case '"':
                case "'":
                    $ic = !$ic;
                    if ($ic) $np++;
                    $ap[$nf][$np] .= $sentence[$i];
                    break;
                case preg_match('/^\w/', $sentence[$i]):
                    if ($ic) {
                        $ap[$nf][$np] .= $sentence[$i];
                    }else {
                        $af[$nf] .= $sentence[$i];
                    }
                    break;
                default:
                    if ($ic) {
                        $ap[$nf][$np] .= $sentence[$i];
                    }
                    break;
            }
        }
        return null;
    }

}