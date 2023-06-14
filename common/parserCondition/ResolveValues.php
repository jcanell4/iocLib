<?php

abstract class abstractResolveValues {
    protected $result = [];

    public function setResult($result) {
        $this->result[] = $result;
    }

    public function getResult() {
        return $this->result;
    }

}

class ResolveValues extends abstractResolveValues {
    private $values;

    public function __construct() {
        $this->values = [
            rslvExtractQString::$className,
            rslvExtractString::$className,
            rslvResolveFunction::$className,
            rslvResolveArray::$className,
            rslvResolveObject::$className
        ];
    }

    public function resolve($param) {
        while ($param) {
            foreach ($this->values as $value) {
                if (call_user_func([$value, 'match'], $param)) {
                    $result = (new $value($this))->getValue($param);
                    $param = $result[0];
                    if (isset($result[1])) {
                        $this->setResult($result[1]);
                    }
                    break;
                }
            }
        }
        return $this->getResult();
    }

}

class rslvResolveFunction {
    public static $className = "rslvResolveFunction";
    protected static $pattern = '/^(\w+)(\(.*)/';

    public static function match($param) {
        return (bool)(preg_match(self::$pattern, $param[0]));
    }

    public static function getValue($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = preg_replace("/${match[0]}[,\s]*/", "", $param, 1);
        $result[] = $match[0];
        return $result;
    }

}

class rslvResolveArray {
    public static $className = "rslvResolveArray";
    protected static $pattern = '/^(\[.*?[^\\]\])(?:(,|))/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

}

class rslvResolveObject {
    public static $className = "rslvResolveObject";
    protected static $pattern = '/^({.*?[^\\]})(?:(,|))/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

}

class rslvExtractQString {
    //extrae, del inicio, textos entre comillas (incluye las comillas escapadas \")
    public static $className = "rslvExtractQString";
    protected static $pattern = '/^(".*?[^\\\\]")(?:(,|))/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public static function getValue($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = preg_replace("/${match[0]}[,\s]*/", "", $param, 1);
        $result[] = $match[1];
        return $result;
    }

}

class rslvExtractString {
    //extrae, del inicio, palabras sin comillas y sin "(" (no funciones) y números enteros y decimales
    public static $className = "rslvExtractString";
    protected static $pattern = '/^(\w+(?:\.\d+)?)(?:,|[^\(\w])/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public static function getValue($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = preg_replace("/${match[0]}[,\s]*/", "", $param, 1);
        $result[] = $match[1];
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
        $ic = false;    //inici cometes
        $ip = false;    //inici paràmetre

        for ($i = 0; $i < strlen($sentence); $i++) { $s = $sentence[$i];
            switch ($sentence[$i]) {
                case '(':
                    $nf++;
                    break;
                case ')':
                    $nf--;
                    break;

                case ',':
                    $ip = true;
                    $np++;
                    break;

                case '"':
                case "'":
                    $ic = !$ic;
                    if ($ic && !$ip) $np++;
                    $ip = false;
                    $ap[$nf][$np] .= $sentence[$i];
                    break;

                case preg_match('/\w/', $sentence[$i], $matches):
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
        return ["af"=>$af, "ap"=>$ap];
    }

}
