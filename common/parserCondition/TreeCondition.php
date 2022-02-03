<?php


abstract class _AbstractNode
{
    protected $tree;
    protected $node;
    protected $key;
    protected $children = [];
    protected $arrays;
    protected $datasource;

    function __construct($tree, $key, $arrays, $datasource)
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

class _TreeParserCondition implements ParserDataInterface
{

    public function parse($text = null, $arrays = [], $dataSource = [], &$resetables = NULL, $generateRoot = TRUE)
    {
        $arrayPattern = "/^\[(.*?)\]$/ms";
        if (preg_match($arrayPattern, $text, $matches)) {
            // ALERTA[Xavi] no cal fer res, s'espera un array en format json
            //$array = explode(',', str_replace(', ', ',', $matches[1]));
            return $text;
        }


        $literalPattern = "/^'(.*?)'|\"(.*?)\"$/ms";
        if (preg_match($literalPattern, $text, $matches)) {
            // és un literal
            // Retornem el darrer element de l'array, quan hi han " es genera un element buit
            $index = count($matches) - 1;
            return $matches[$index];
        }

        $datePattern = "/\d\d\d\d-\d\d-\d\d/ms";
        if (preg_match($datePattern, $text, $matches)) {
            // és una data, ho tractem com string perquè ja estan ordenades en format yyyy-mm-dd
            return $text;
        }

        // Comprovem si és un subset
        if (strpos($text, ".") !== FALSE) {
            $tokens = explode(".", $text);
            // fem servir el datasource, el primer token és el subset
            $subset = $tokens[0];
            $field = $tokens[1];

            if (!isset($dataSource[$subset])) {
                return "Subset desconegut: $subset\n";
            }

            if (!isset($dataSource[$subset][$field])) {
                return "Camp desconegut $field ald subset $subset\n";
            }

            return $dataSource[$subset][$field];
        }

        // Es tracta d'un camp
        $field = $arrays[$text];

        if ($field !== NULL) {
            return $field;
        } else {
            return "Camp desconegut $text";
        }
    }

    public function getValue($text = null, $arrays = [], $dataSource = [], &$resetables = NULL, $generateRoot = TRUE)
    {
        // ALERTA! Aquest no es fa servir, sembla que només el crida el Wioccl (a la classe original)
        return "TODO value\n";
    }
}

