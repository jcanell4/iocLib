<?php
require_once "WiocclParser.php";
/*
 * Assigna un valor a una variable i la manté disponible per a qualsevol instrucció que contingui (entre la obetura i el tancament).
 * paràmetres:
 *    var: nom de la variable
 *    value: valor a assignar a la variable
 *    type: tipus de assignació. Pot ser literal o map. Si type és 'literal' el valor assignat a la variable serà el valor literal contingut a l'atribut value.
 *          si type és map, l'atribut value actuarà com un clau del conjunt de valors cintinguts a l'atribut map.
 *    map: array de valors que mapegen el valor a assignar a la variable
 * Syntax:
 * <WIOCCL:SET var="itinerari" type="literal" value="{##itinerarisRecomanats[0]##}"> 
 *   //dins d'aquets context la variable itinerari tindrà el valor contingut a la posició 0 del camp itinerarisRecomanats
 *   //Així en aquest context, l'expressió {##itinerari##} serà reconeguda
 * </WIOCCL:SET>
 * 
 * <WIOCCL:SET var="nbloc" type="map" value="{##tipusBlocModul##}" map="{''mòdul'':0,''1r. bloc'':1,''2n. bloc'':2,''3r. bloc'':3}">
 *   //dins d'aquets context la variable nbloc tindrà el valor numèric corresponent a l'item de l'array contingut a map que tingui una clai amb el valor 
 *   //corresponent a l'atribut value. Així en aquest context, l'expressió {##nBloc##} serà reconeguda. Si tipusBlocModul valgués 1r. bloc, nBloc valdria 1
 * </WIOCCL:SET>
 */

class WiocclSet extends WiocclInstruction {
    const VAR_ATTR = "var";    
    const TYPE_ATTR = "type";    
    const MAP_ATTR = "map";    
    const VALUE_ATTR = "value";    
    const MAP_TYPE = "map";    
    const LITERAL_TYPE = "literal";    
    
    public function __construct($value = null, $arrays = [], $dataSource=[], &$resetables=NULL, &$parentInstruction=NULL){
        parent::__construct($value, $arrays, $dataSource, $resetables, $parentInstruction);

        // Com que el subset només serveix per establir valors no generem nodes a la estructura per de cap atribut

        $this->pauseStructureGeneration();

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


        $this->resumeStructureGeneration();
    }
}
