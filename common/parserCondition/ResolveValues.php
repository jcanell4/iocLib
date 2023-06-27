<?php

abstract class abstractResolveValues {
    protected static $parser;
    protected static $arrays = [];
    protected static $dataSource = [];
    protected $mainParam;
    protected $delimiter;
    protected $toParse;

    public function foreing_construct($parser=NULL, $arrays=[], $dataSource=[]) {
        self::$parser = $parser;
        self::$arrays = $arrays;
        self::$dataSource = $dataSource;
    }

    public function init($mainParam="", $delimiter="", $toParse="") {
        $this->mainParam = $mainParam;
        $this->delimiter = $delimiter;
        $this->toParse = $toParse;
    }

    public function getParser() {
        return self::$parser;
    }
    public function getArrays() {
        return self::$arrays;
    }
    public function getDataSource() {
        return self::$dataSource;
    }
}

class stackResolveValues extends abstractResolveValues {
    private $resolvers = [
                "rslvResolveFunction",
                "rslvExtractQString",
                "rslvExtractString",
                "rslvResolveArray",
                "rslvResolveObject",
                "rslvResolveTerminator"
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
                    if ($extract[1] !== "," && !empty($extract[1])) {
                        if (in_array($extract[1], [")","}","]"])) {
                            $this->pila[] = $instance->getValue();
                            return $this->pila;
                        }else {
                            $this->pila[] = $instance->parse($param);
                        }
                    }
                    $this->pila[] = $instance->getValue();
                    break;
                }
            }
        }
        return $this->pila;
    }

}

class ResolveValues extends stackResolveValues {

    public function resolve($param) {
        $result = [];
        $pilas = parent::parse($param);
        foreach ($pilas as $value) {
            if (!is_array($value)) {
                $result[] = $value;
            }
        }
        return $result;
    }

}

class rslvResolveFunction extends stackResolveValues {
    public static $className = "rslvResolveFunction";
    protected static $pattern = '/^(\w+)(\()(.*)/';

    public static function match($param) {
        return (bool)(preg_match(self::$pattern, $param));
    }
    
    public function getValue() {
        $funcName = $this->mainParam;
        $parsedParams = [];
        foreach($this->pila as $param) {
            $parsedParams[] = $this->getParser()->parse($param, $this->getArrays(), $this->getDataSource());
        }
        $result = call_user_func_array(["IocCommonFunctions", $funcName], $this->pila);
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

class rslvResolveArray extends stackResolveValues {
    public static $className = "rslvResolveArray";
    //protected static $pattern = '/^(\[.*?[^\\\\]\])(?:(,|))/';
    protected static $pattern = '/^(\[)(.*)$/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public function getValue() {
        return $this->pila;
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

class rslvResolveObject extends stackResolveValues {
    public static $className = "rslvResolveObject";
    //protected static $pattern = '/^({.*?[^\\\\]})(?:(,|))/';
    protected static $pattern = '/^({)(.*)$/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public function getValue() {
        return $this->pila;
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

class rslvResolveTerminator extends stackResolveValues {
    public static $className = "rslvResolveTerminator";
    protected static $pattern = '/^(,|}|\]|\))(.*)$/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public function getValue() {
        return $this->mainParam;
    }

    public function extract($param) {
        $result = [];
        preg_match(self::$pattern, $param, $match);
        $result[] = trim($match[1]);    //terminator
        $result[] = trim($match[1]);
        $result[] = trim($match[2]);
        return $result;
    }

}

class rslvExtractQString extends stackResolveValues {
    //extrae, del inicio, textos entre comillas (incluye las comillas escapadas \")
    public static $className = "rslvExtractQString";
    //protected static $pattern = '/^(".*?[^\\\\]")(,|$|\W[^\(\w])/';
    protected static $pattern = '/^(".*?[^\\\\]")(,|\)|\]|})?(.*)$/';

    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public function getValue() {
        return $this->mainParam;
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
    //protected static $pattern = '/^(\w+(?:\.\d+)?)[^\(]?(,|\)|\]|})(.*)$/';
    protected static $pattern = '/^(\w+(?:\.\d+)?)(,|\)|\]|})?(.*)$/'; //Aquest inclou noms de funcions: nom(
                                                                       //per tant s'ha d'executar després
    public static function match($param) {
        return (bool)preg_match(self::$pattern, $param);
    }

    public function getValue() {
        return $this->mainParam;
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
