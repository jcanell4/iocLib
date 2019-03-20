<?php
require_once "WiocclParser.php";

class WiocclInsert extends WiocclInstruction
{

    protected function getContent($token)
    {

        // TODO: Si arriba aquí es perque s'està fent una substitució, desar al registre:
        //      $id del document actual
        //      $ns del document inserit.

        // El registre hauria de permetre comprovar: Fitxers inserits i Fitxers als que s'ha inserit. No cal distinguir
        // la quantitat de vegades que s'ha inserit un fitxer en un mateix document perquè l'estat es "reseteja" cada vegada que es desa
        // Semblant a això (els identificadors son els ns/id)
//        {
//            docA: {inserit: [docB, docC], insereix: [docX],
//            docB: {inserit: [], insereix [docA]}
//            docC: {inserit: [], insereix [docA]}
//            docX: {inserit: [docA], insereix: []}
//        }


        // Per assegurar que si s'esborra un fitxer es neteja abans de fer el parse d'un document s'eliminaran tots els enllaços desats
        // i es tornaran a fer durant el parse.

        // Quan s'elimini un document:
        //      Primerament es comprovará que cap fitxer apunti a aquest
        //          Si un fitxer apunta a aquest s'haurà de comprovar si el fitxer existeix
        //          Si cap fitxer vàlid apunta permetrà esborrar
        //              S'eliminarà a si mateix de la llista dels fitxers als que apuntés.

        // Exemple, volem esborrar docA
        // if (count(docA['inserit'])>0) {
        //      Recorrem tots els ns fins que trobem un que existeixi, només cal un per bloquejar la eliminació.
        //      En aquest cas es comprovaria docB i retornaria un missatge d'error: "El fitxer no pot ser eliminat perque ès inserit a docB i docC
        // }
        //
        // Exemple, volem esborrar docB
        // if (count(docB['inserit'])>0) {
        //      No ho es, així quen o cal comprovar res més, procedim a la eliminació
        //      Recorrem tots els valors de docB['insereix'] i eliminem docB de la seva llista d'inserits, de manera que quedarà -> docA {inserit: [docC], insereix: [docX]}.
        //      Procedim a esborrar el fitxer docB.



        $ns = 'ERROR: NS not found';

        if (preg_match('/ns="(.*?)"/', $token['value'], $matches) === 1) {
            $ns = $matches[1];
        }

        if (page_exists($ns)) {
            $filename = wikiFN($ns);

            $ret= file_get_contents($filename); // Aquest es el parse de la wiki
            return $ret;

        }

    }
}