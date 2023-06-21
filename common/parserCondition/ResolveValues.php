<?php

abstract class abstractResolveValues {
    protected $name;
    protected $delimiter;
    protected $toParse;

    public function init($name="", $delimiter="", $toParse="") {
        $this->name = $name;
        $this->delimiter = $delimiter;
        $this->toParse = $toParse;
    }
/*
 * Expresión regular para extraer los parámetros, separados por comas, de una función
(?(DEFINE)
  (?<functionname>
    [^\W\d]\w*
  )

  (?<string>
    (?<quote>["'])
    (?:\\.|(?!\k<quote>).)*
    \k<quote>
  )
  (?<number>
    \d+(?:\.\d+)?
  )
  (?<functioncall>
    \g<functionname>\(\s*
    \g<expression>(?:\s*,\s*\g<expression>)*
    \s*\)
  )

  (?<expression>
    \g<string>|\g<number>
  |
    \g<functioncall>|\g<functionname>
  )
)

(?:\g<functionname>\(\s*|\G,\s*)\K(\g<expression>)
*/
}

class stackResolveValues extends abstractResolveValues {
    private $resolvers = [
                "rslvResolveFunction",
                "rslvExtractQString",
                "rslvExtractString",
                "rslvResolveArray",
                "rslvResolveObject"
            ];
    protected $pila = [];

    public function parse($param) {
        while ($param) {
            foreach ($this->resolvers as $resolver) {
                if (call_user_func([$resolver, 'match'], $param)) {
                    $instance = new $resolver();
                    $extract = $instance->extract($param);
                    $param = $extract[2];
                    $instance->init($extract[0], $extract[1], $param);
                    if ($extract[1] != "," && !in_array($param[0], [")","]"])) {
                        $this->toParse = $instance->parse($param);
                    }
                    $this->pila[] = $instance;
                }
            }
        }
        return $this->toParse;
    }

}

class ResolveValues extends stackResolveValues {

    public function resolve($param) {
        return parent::parse($param);
    }

}

class rslvResolveFunction extends stackResolveValues {
    public static $className = "rslvResolveFunction";
    protected static $pattern = '/^(\w+)(\()(.*)/';

    public static function match($param) {
        return (bool)(preg_match(self::$pattern, $param));
    }

    public function extract($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = trim($match[1]);
        $result[] = trim($match[2]);
        $result[] = trim($match[3]);
        return $result;
    }

}

class rslvResolveArray extends stackResolveValues {
    public static $className = "rslvResolveArray";
    //protected static $pattern = '/^(\[.*?[^\\\\]\])(?:(,|))/';
    protected static $pattern = '/^(\[)(.*)$/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }
    public static function getValue($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        return $result;
    }

    public function extract($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = trim($match[1]);
        $result[] = "";
        $result[] = trim($match[2]);
        return $result;
    }

}

class rslvResolveObject {
    public static $className = "rslvResolveObject";
    //protected static $pattern = '/^({.*?[^\\\\]})(?:(,|))/';
    protected static $pattern = '/^({)(.*)$/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }
    public static function getValue($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        return $result;
    }

    public function extract($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = trim($match[1]);
        $result[] = "";
        $result[] = trim($match[3]);
        return $result;
    }

}

class rslvExtractQString extends stackResolveValues {
    //extrae, del inicio, textos entre comillas (incluye las comillas escapadas \")
    public static $className = "rslvExtractQString";
    //protected static $pattern = '/^(".*?[^\\\\]")(,|$|\W[^\(\w])/';
    protected static $pattern = '/^(".*?[^\\\\]")(,|$|\W[^\(\w])(.*)$/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public static function getValue($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = preg_replace("/${match[0]}[,\s]*/", "", $param, 1);
        $result[] = $match[0];
        return $result;
    }

    public function extract($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = trim($match[1]);
        $result[] = trim($match[2]);
        $result[] = trim($match[3]);
        return $result;
    }

}

class rslvExtractString extends stackResolveValues {
    //extrae, del inicio, palabras sin comillas y sin "(" (no funciones) y números enteros y decimales
    public static $className = "rslvExtractString";
    //protected static $pattern = '/^(\w+(?:\.\d+)?)(,|$|\W[^\(\w])/';
    protected static $pattern = '/^(\w+(?:\.\d+)?)(,|$|\W[^\(\w])(.*)$/';

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

    public function extract($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = trim($match[1]);
        $result[] = trim($match[2]);
        $result[] = trim($match[3]);
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
