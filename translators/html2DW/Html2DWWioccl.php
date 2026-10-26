<?php
require_once "Html2DWParser.php";

class Html2DWWioccl extends Html2DWInstruction {

    static public $processedRefs = [];

    protected function getContent($token) {
        preg_match($token['pattern'], $token['raw'], $match);
        $refId = $match[1];


        // Al WiocclStructureItem->toWioccl s'afegeixen les referències processades
        // si ja s'afegit no cal tornar-lo a afegir al document, per això es retorna buit
        // la raó per la que és necessari es que a l'editor s'envien totes les etiquetes d'apertura
        // encara que aquestes es trobin dintre d'altres elements (per exemple els for i choose)
        // però en fer el parse tot això és reconstrueix al toWioccl i el $tokenKey NO AVANÇA
        //
        // En resum: si ja s'ha processat un node no cal processar cap token amb aquesta referència
        if (in_array($refId, self::$processedRefs)) {
            return '';
        }

//        $structure = Html2DWParser::$structure;

        return Html2DWParser::$structure[$refId]->toWioccl();
    }
}