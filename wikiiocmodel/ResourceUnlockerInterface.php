<?php

interface ResourceUnlockerInterface
{
    const UNLOCKED = 100; // indica que s'ha desbloquejat si el paràmetre era TRUE i que s'ha eliminat del registre estès de bloquejos, la referència a l'usuari que mantenia el bloqueig. Si el paràmetre era FALSE, també s'elimina l'entrada del recurs estés però no es desbloqueja el fitxer.
    const LEAVED = 200; // indica que la petició l'ha realitzat un usuari que no tenia el bloqueig actiu, sinó que prèviament havia fet la petició però ha cancel·lat la demanda abans d'aconseguir el bloqueig.
    const OTHER = 400; // L'usuari que fa la petició, no té el bloqueig ni tampoc tenia demanada cap petició.

    /**
     * Es tracta del mètode que hauran d'executar en iniciar el desbloqueig o també quan l'usuari cancel·la la demanda
     * de bloqueig. Per  defecte no es desbloqueja el recurs, perquè actualment el desbloqueig es realitza internament
     * a les funcions natives de la wiki. Malgrat tot, per a futurs projectes es contempla la possibilitat de fer el
     * desbloqueig directament aquí, si es passa el paràmetre amb valor TRUE. EL mètode retorna una constant amb el
     * resultat obtingut de la petició.
     *
     * @param bool $unlock
     * @return int
     */
    public function leaveResource($unlock = FALSE);    
}