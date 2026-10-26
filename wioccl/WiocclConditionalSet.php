<?php
require_once "WiocclParser.php";
/*
 * Fa una assignació a una variable si i només si es compleix una determinada condició. L'assigació es fa de forma identica 
 * a la realitzada amb la instrucció <WIOCCL:SET>. 
 * Atributs:
 *   - condition: conté la condició que cal complir per a fer l'assignació 
 *   - var: no de la variabla a la que es farà l'assignació
 *   - value: Valor o clau de l'assignació
 *   - type: literal o map (vegeu <WIOCCL:SET>)
 *   - map: array que servirà par mapejar l'assignació  arealitzar a partir de value usat com a key (vegeu <WIOCCL:SET>)
 * 
 * Syntax:
 *   <WIOCCL:CONDSET condition="{#_STR_CONTAINS(''/'', ''{##nomPeriode##}'')_#}" var="nomPeriodeSing" type="literal" value="{#_ARRAY_GET_VALUE(0,{#_EXPLODE(''/'',''{##nomPeriode##}'')_#})_#}">
 *   <WIOCCL:CONDSET condition="{#_STR_CONTAINS(''/'', ''{##nomPeriode##}'')_#}" var="nomPeriodePlur" type="literal" value="{#_ARRAY_GET_VALUE(1,{#_EXPLODE(''/'',''{##nomPeriode##}'')_#})_#}">
 *   <WIOCCL:CONDSET condition="!{#_STR_CONTAINS(''/'', ''{##nomPeriode##}'')_#}" var="nomPeriodeSing" type="literal" value="{##nomPeriode##}">
 *   <WIOCCL:CONDSET condition="!{#_STR_CONTAINS(''/'', ''{##nomPeriode##}'')_#}" var="nomPeriodePlur" type="literal" value="{##nomPeriode##}s">
 *          //Si el camp nomPeriode conté comes, significa que contè a més del nom, la forma plural i s'aasigna a dues noves variables (nomPeriodeSing i nomPeriodePlur) 
 *          //ambdues formes respectivament. Si no conté comes significa que només conté el nom en singular i es dedueix el nom plural afegint una s al final
 *          //En aquest context es pot usar les expressions {##nomPeriodeSing##} i {##nomPeriodePlur##}
 *   </WIOCCL:CONDSET>
 *   </WIOCCL:CONDSET>
 *   </WIOCCL:CONDSET>
 *   </WIOCCL:CONDSET>


 */

class WiocclConditionalSet extends WiocclInstruction {
    const COND_ATTR = "condition";
    const VAR_ATTR = "var";    
    const TYPE_ATTR = "type";    
    const MAP_ATTR = "map";    
    const VALUE_ATTR = "value";    
    const MAP_TYPE = "map";    
    const LITERAL_TYPE = "literal";    

    public function __construct($value = null, $arrays = [], $dataSource=[], &$resetables=NULL, &$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        $this->pauseStructureGeneration();

        $evaluation= $this->evaluateCondition($this->extractVarName($value, self::COND_ATTR, true));

        $this->resumeStructureGeneration();

        if($evaluation){
//        if($this->evaluateCondition($this->extractVarName($value, self::COND_ATTR, true))){
            $rawVarName = $this->extractVarName($value, self::VAR_ATTR);
            $type = $this->extractVarName($value, self::TYPE_ATTR, FALSE);
            if(empty($type)){
                $type = self::LITERAL_TYPE;
            }
            $rawValue = $this->extractVarName($value, self::VALUE_ATTR);
            $varName = $this->normalizeArg(WiocclParser::parse($rawVarName, $arrays, $dataSource, $resetables ));
            $v = $this->normalizeArg(WiocclParser::parse($rawValue, $arrays, $dataSource, $resetables));

            if ($type === self::LITERAL_TYPE) {
                $this->resetables->setValue($varName, $v);
            } elseif ($type === self::MAP_TYPE) {
                $map = $this->extractMap($value, self::MAP_ATTR);
                $this->resetables->setValue($varName, $map[$v]);
            }            
        }
    }

    private function evaluateCondition($strCondition){
        $_condition = new _WiocclCondition($strCondition);
        $_condition->parseData($this->getArrays(), $this->getDataSource(), $this->resetables);
        return $_condition->validate();        
    }
}
