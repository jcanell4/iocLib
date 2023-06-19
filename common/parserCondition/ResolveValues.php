<?php

abstract class abstractResolveValues {
    protected $name;
    protected $toParse;

    public function init($name, $toParse) {
        $this->name = $name;
        $this->toParse = $toParse;
    }

}

abstract class stackResolveValues extends abstractResolveValues {
    protected $pila = [];
    protected $resolvers = [
                "rslvResolveFunction",
                "rslvExtractQString",
                "rslvExtractString",
                "rslvResolveArray",
                "rslvResolveObject"
            ];

    public function parse($param) {
        while ($param) {
            foreach ($this->resolvers as $resolver) {
                if (call_user_func([$resolver, 'match'], $param)) {
                    $instance = new $resolver();
                    $instance->init($param[0], $param[1]);
                    $toParse = $instance->parse($param);
                    $this->pila = $instance;
                }
            }
        }
        return $toParse;
    }

}

class ResolveValues extends stackResolveValues {

    public function resolve($param) {
        $toParse = $this->parse($param);
        return $toParse;
    }

}

class rslvResolveFunction extends stackResolveValues {
    public static $className = "rslvResolveFunction";
    protected static $pattern = '/^(\w+)(\()(.*)/';

    public static function match($param) {
        return (bool)(preg_match(self::$pattern, $param));
    }

    public function parse($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = $match[0];
        $result[] = $match[1];
        $result[] = $match[2];
        return $result;
    }

}

class rslvResolveArray extends abstractResolveValues {
    public static $className = "rslvResolveArray";
    protected static $pattern = '/^(\[.*?[^\\\\]\])(?:(,|))/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }
    public static function getValue($param, &$pila=[], &$nf=0, &$na=0, &$no=0) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        return $result;
    }

}

class rslvResolveObject {
    public static $className = "rslvResolveObject";
    protected static $pattern = '/^({.*?[^\\\\]})(?:(,|))/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }
    public static function getValue($param, &$pila=[], &$nf=0, &$na=0, &$no=0) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        return $result;
    }

}

class rslvExtractQString extends abstractResolveValues {
    //extrae, del inicio, textos entre comillas (incluye las comillas escapadas \")
    public static $className = "rslvExtractQString";
    protected static $pattern = '/^(".*?[^\\]")(,|$|\W[^\(\w])/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public static function getValue($param, &$pila=[], &$nf=0, &$na=0, &$no=0) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = preg_replace("/${match[0]}[,\s]*/", "", $param, 1);
        $result[] = $match[0];
        $pila[] = $match[0];
        for ($i=0; $i<strlen($match[1]); $i++) {
            switch ($match[1][$i]) {
                case ")": $nf--; break;
                case "]": $na--; break;
                case "}": $no--; break;
            }
        }
        return $result;
    }

}

class rslvExtractString extends abstractResolveValues {
    //extrae, del inicio, palabras sin comillas y sin "(" (no funciones) y números enteros y decimales
    public static $className = "rslvExtractString";
    protected static $pattern = '/^(\w+(?:\.\d+)?)(,|$|\W[^\(\w])/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public static function getValue($param, &$pila=[], &$nf=0, &$na=0, &$no=0) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = preg_replace("/${match[0]}[,\s]*/", "", $param, 1);
        $result[] = $match[1];
        $pila[] = $match[1];
        for ($i=0; $i<strlen($match[2]); $i++) {
            switch ($match[2][$i]) {
                case ")": $nf--; break;
                case "]": $na--; break;
                case "}": $no--; break;
            }
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
