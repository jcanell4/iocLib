<?php


abstract class _AbstractNode
{
    protected $tree;
    protected $node;
    protected $key;
    protected $children = [];
    protected $arrays;
    protected $datasource;

    public function __construct($tree, $key, $arrays, $datasource)
    {
        $this->tree = $tree;
        $this->key = $key;
        $this->node = $tree[$key];
        $this->arrays = $arrays;
        $this->datasource = $datasource;
    }

    public abstract function getValue();

}


class _NodeAggregation extends _AbstractNode
{
    /** @override */
    public function getValue()
    {
        $primer = TRUE;
        $resultat = NULL;

        $operator = $this->node['connector'];

        foreach ($this->getChildren() as $child) {
            if ($primer) {
                $primer = FALSE;
                $resultat = $child->getValue();
            } else if ($operator == 'or') {
                $resultat |= $child->getValue();
                // Aquí es pot aplicar la optimització
                // if ($resultat) break;
            } else if ($operator == 'and') {
                $resultat &= $child->getValue();
                // Aquí es pot aplicar la optimització
                // if (!$resultat) break;
            } else {
                // Només s'utiliza el primer valor
                $resultat = $child->getValue();
                break;
            }
        }

        return $resultat;
    }

    function getChildren()
    {

        if (count($this->children) == 0 && count($this->node['elements']) > 0) {
            foreach ($this->node['elements'] as $key) {
                $children = NodeFactory::getNode($this->tree, $key, $this->arrays, $this->datasource);
                $this->children[] = $children;
            }
        }

        return $this->children;
    }

}

class _NodeCondition extends _AbstractNode
{
    /** @override */
    public function getValue()
    {
        $primer = TRUE;
        $resultat = NULL;

        $operator = $this->node['connector'];

        foreach ($this->node['elements'] as $strCondition) {

            $condition = $this->getCondition($strCondition);

            if ($primer) {
                $primer = FALSE;
                $resultat = $condition;
            } else if ($operator == 'or') {
                $resultat |= $condition;
                // Aquí es pot aplicar la optimització
                // if ($resultat) break;
            } else if ($operator == 'and') {
                $resultat &= $condition;
                // Aquí es pot aplicar la optimització
                // if (!$resultat) break;
            } else {
                // Només s'utiliza el primer valor
                $resultat = $condition;
                break;
            }
        }

        return $resultat;
    }

    private function getCondition($strCondition)
    {
        $_condition = new _TreeCondition($strCondition);
        //$_condition = new _WiocclCondition($strCondition);
        $nullResetables = NULL;
        $_condition->parseData($this->arrays, $this->datasource, $nullResetables);

        $resultat = $_condition->validate();

        return $resultat;
    }


}


class NodeFactory
{

    public static function getNode($tree, $key, $arrays, $datasource)
    {
        $node = NULL;
        if ($tree[$key]['type'] == "aggregation") {
            $node = new _NodeAggregation($tree, $key, $arrays, $datasource);
        } else {
            $node = new _NodeCondition($tree, $key, $arrays, $datasource);
        }


        return $node;
    }
}

class _TreeCondition extends _BaseCondition
{

    public function __construct($strCondition)
    {
        $parser = new _TreeParserCondition();
        parent::__construct($strCondition, $parser);
    }

}


abstract class AbstractInstruction
{

    protected $content = NULL;
    protected $parser = NULL;
    static public $className = "AbstractInstruction";

    public function __construct($parser)
    {
        $this->parser = $parser;
    }

    abstract static public function match($text);

    abstract public function getValue($text = null, $arrays = [], $dataSource = []);
}

class DefaultInstruction extends AbstractInstruction
{

    static public $className = "DefaultInstruction";
    
    public function getValue($text = null, $arrays = [], $dataSource = [])
    {
        return $text;
    }

    public static function match($text)
    {
        return true;
    }
}

class LiteralInstruction extends AbstractInstruction
{
    static public $className = "LiteralInstruction";
    static protected $pattern = "/^(true)$|^(TRUE)$|^(FALSE)$|^(false)$|^'(.*?)'$|^\"(.*?)\"$|^\d+\.?\d*$/ms";
    //static protected $pattern = "/^'(.*?)'$|^\"(.*?)\"$/ms";

    static public function match($text)
    {
        return (bool)preg_match(self::$pattern, $text);
    }

    public function getValue($text = null, $arrays = [], $dataSource = [])
    {
        if (preg_match(self::$pattern, $text, $matches)) {
            $index = count($matches) - 1;
            $content = $matches[$index];

            // ALERTA! encara que a normalizeArg() ja es fa la conversió dels strings a boolean, int o float
            // es necessari fer aquí el tractament perquè si no no s'aplica als arrays

            $content = IocCommonFunctions::normalizeArg($content);

            return $content;


        } else {
//            return "[Bad Format: Literal]";
            return null;
        }

    }
}

class ArrayInstruction extends AbstractInstruction
{
    static public $className = "ArrayInstruction";
    static protected $pattern = "/^\[(.*?)\]$/ms";

    static public function match($text)
    {
        return (bool)preg_match(self::$pattern, $text);
    }

    public function getValue($text = null, $arrays = [], $dataSource = [])
    {
        if (preg_match(self::$pattern, $text, $matches)) {

            // ALERTA! la versió original retornava el mateix text, aquesta separa els elements
            // fa el parse de cadascun i retorna el string amb els valors finals

            // ALERTA!! Això no és correcte, si es troba una coma dins d'un literal també faria el explode!

            $array = IocCommonFunctions::extractComaSeparatedValues($matches[1]);

//            $patternParams =  '/ ?(".*?")| ?(?:,)| ?(\d+\.?\d*?)| ?(.*),| ?(.*)/m';
//            if (preg_match_all($patternParams, $matches[1], $matchParams,  PREG_SET_ORDER)) {
//
//                // El darrer element sempre és buit
//                for ($i =0; $i<count($matchParams)-1; $i++) {
//                    $value = $matchParams[$i][count($matchParams[$i])-1];
//                    if (count($matchParams[$i])>1 ) {
//                        $array[] = $value;
//                    }
//                }
//            }


            //$array = explode(',', str_replace(', ', ',', $matches[1]));

            $elements = [];

            foreach ($array as $element) {
                $elements[] = $this->parser->parse($element, $arrays, $dataSource);
            }

            return $elements;
            //return "[" . implode(",", $elements) . "]";

        } else {
//            return "[Bad Format: Array]";
            return null;
        }

    }
}

class DateInstruction extends AbstractInstruction
{
    static public $className = "DateInstruction";
    static protected $pattern = "/\d\d\d\d-\d\d-\d\d/ms";

    static public function match($text)
    {
        return (bool)preg_match(self::$pattern, $text);
    }

    public function getValue($text = null, $arrays = [], $dataSource = [])
    {
        if (preg_match(self::$pattern, $text, $matches)) {
            return $text;

        } else {
//            return "[Bad Format: Date]";
            return null;
        }

    }
}

class FieldInstruction extends AbstractInstruction
{
    static public $className = "FieldInstruction";
    protected $pattern = "/\d\d\d\d-\d\d-\d\d/ms";

    static public function match($text)
    {
        return TRUE;
    }

    public function getValue($text = null, $arrays = [], $dataSource = [])
    {
        // Es tracta d'un camp
        $field = $arrays[$text];

        if ($field !== NULL) {
            return $field;
        } else {
            return null;
//            return "[Unknown Field: $text]";
        }

    }
}

class SubsetInstruction extends AbstractInstruction
{
    static public $className = "SubsetInstruction";

    static public function match($text)
    {
        return strpos($text, ".") !== FALSE;
    }

    public function getValue($text = null, $arrays = [], $dataSource = [])
    {
        if (strpos($text, ".") === FALSE) {
            return null;
//            return "[Bad Format: Subset]";
        }

        $tokens = explode(".", $text);
        // fem servir el datasource, el primer token és el subset
        $subset = $tokens[0];
        $field = $tokens[1];


        if (!isset($dataSource[$subset])) {
            return null;
            //return "[Unknown Subset : $subset]";
        }

        // TODO: El parse del subset fa un parse de la següent part,
        // però passant com a $array el $datasource!!
        return $this->parser->parse($field, $dataSource[$subset], $dataSource);


//        return $dataSource[$subset][$field];
    }

}

class ObjectInstruction extends AbstractInstruction
{
    static public $className = "ObjectInstruction";

    static public function match($text)
    {
        return strpos($text, "#") !== FALSE;
    }

    public function getValue($text = null, $arrays = [], $dataSource = [])
    {
        if (strpos($text, "#") === FALSE) {
//            return "[Bad Format: Object]";
            return null;
        }

        $tokens = explode("#", $text);
        // fem servir el datasource, el primer token és el camp
        $obj = $tokens[0];
        $prop = $tokens[1];

        // La clau sempre ha d'anar entre cometes dobles (JSON), per tant s'ha de treure
        // el primer i el darrer caràcter de la clau
        $prop = substr($prop, 1, strlen($prop)-2);

        if (!isset($arrays[$obj])) {
            return null;
//            return "[Unknown Field: $obj]";
        }

        $json = json_decode($arrays[$obj], true);
        $field = $json[$prop];
        // És un json, el deserialitzem

        if ($field !== NULL) {
            // TODO: Determinar si hem de fer un parse del contingut?
            return $field;
        } else {
            return null;
//            return "[Unknown Prop: $prop]";
        }

    }

}

class RowInstruction extends AbstractInstruction
{
    static public $className = "RowInstruction";
    static protected $pattern = "/(.*?)\[(.*?)\]/ms";

    static public function match($text)
    {
        return (bool)preg_match(self::$pattern, $text);

    }

    public function getValue($text = null, $arrays = [], $dataSource = [])
    {

        if (!preg_match(self::$pattern, $text, $matches)) {
            return null;
//            return "[Bad Format: Row]";
        }

        $field = $matches[1];
        $index = is_numeric($matches[2]) ? intval($matches[2]) : $matches[2];

        if (!isset($arrays[$field])) {
            return null;
//            return "[Unknown Field: $text]";
        }

        $value = $arrays[$field][$index];

        $propPos = strpos($text, "#");
        if ($propPos === FALSE){
            return $value;
        }

        // És un objecte
        $prop = substr($text, $propPos+1, strlen($text)-1);

        $json = json_decode($value, true);

        $content = isset($json[$prop]) ? $json[$prop] : null;
//        $content = isset($json[$prop]) ? $json[$prop] : "[Unknown Prop: $prop]";

        return $content;

    }

}

class FunctionInstruction extends AbstractInstruction
{
    static public $className = "FunctionInstruction";
    static protected $pattern = "/(.*?)\((.*?)\)/ms";

    static public function match($text)
    {
        return (bool)preg_match(self::$pattern, $text);

    }

    public function getValue($text = null, $arrays = [], $dataSource = [])
    {

        if (!preg_match(self::$pattern, $text, $matches)) {
//            return "[Bad Format: Function]";
            return null;
        }

        $funcName = $matches[1];
        // ALERTA! Els params poden incloure arrays, crides a altres funcions, etc.

        $params = IocCommonFunctions::extractComaSeparatedValues($matches[2]);

        $parsedParams = [];
        for ($i =0; $i<count($params); $i++) {
            $parsedParams[] = $this->parser->parse($params[$i], $arrays, $dataSource);
        }

        $method = array("IocCommonFunctions", $funcName);
        if(is_callable($method)){
            try{
                $result = call_user_func_array($method, $parsedParams);
            } catch (Error $e){
                $result = $e->getMessage();
            }
        }else{
//            $result = "[ERROR! No existeix la funció ${$method[1]}]";
            $result = null;
        }

        return $result;

    }

}

class _TreeParserCondition implements ParserDataInterface
{

    private $instructions;

    public function __construct()
    {
        // ALERTA[Xavi] en alguns casos l'ordre importa! (s'ha de consultar cada classe per separat)
        // el tipus camp sempre ha de ser el darrer

        $this->instructions = [
            ArrayInstruction::$className,
            LiteralInstruction::$className,
            DateInstruction::$className,

            SubsetInstruction::$className,

            // Aquest sempre seràn els darrers, l'ordre és important
            FunctionInstruction::$className,
            RowInstruction::$className,
            ObjectInstruction::$className,

            FieldInstruction::$className,

        ];
    }

    public function parse($text = null, $arrays = [], $dataSource = [], &$resetables = NULL, $generateRoot = TRUE)
    {
        foreach ($this->instructions as $instruction) {
            if (call_user_func([$instruction, 'match'], $text)) {
                return (new $instruction($this))->getValue($text, $arrays, $dataSource);
            }
        }

//        return "[Unknown instruction for: $text]";
        return null;

    }

    public function getValue($text = null, $arrays = [], $dataSource = [], &$resetables = NULL, $generateRoot = TRUE)
    {
        // Es crida quan a la condició la expressió s'avalua com un literal, per exemple en el cas de les funcions
        // sense operador ni segón argument.
        return $this->parse($text, $arrays, $dataSource, $resetables, $generateRoot);
//        return "TODO value\n";
    }
}

