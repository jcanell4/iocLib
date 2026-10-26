<?php

interface ResourceLockerInterface
{
    const LOCKED = 100; // indica que es pot bloquejar el recurs sense cap problema o bé que s'ha bloquejat si el paràmetre fos CERT. Tot i així s'enregistra el bloqueig en el registra estès de bloquejos (Extended Lock File). L'extended lock file és un fitxer informatiu on trobarem la informació de l'usuari que estigui bloquejant un recurs i també d'aquells usuaris que expressin el seu desig de bloquejar-lo. Cada recurs disposarà d'un fitxer diferent.
    const REQUIRED = 200; // indica que el recurs estava ja bloquejat per un altre usuari i no s'ha pogut bloquejar, però que s'ha posta la petició de bloqueig a la cua de peticions, expressant el desig de bloqueig, al fitxer Extended Lock File.
    const LOCKED_BEFORE = 400; // LOCKED_BEFORE: indica que l'usuari ja ha bloquejat prèviament el mateix recurs, probablement desd 'un altre ordinador.

    /**
     * Es tracta del mètode que hauran d'executar en iniciar el bloqueig. Per  defecte no bloqueja el recurs, perquè
     * actualment el bloqueig es realitza internament a les funcions natives de la wiki. Malgrat tot, per a futurs
     * projectes es contempla la possibilitat de fer el bloqueig directament aquí, si es passa el paràmetre amb valor
     * TRUE. EL mètode comprova si algú està bloquejant ja el recurs i en funció d'això, retorna una constant amb el
     * resultat obtingut de la petició.
     *
     * @param bool $lock
     * @return int
     */
    public function requireResource($lock = FALSE);
}