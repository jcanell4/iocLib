<?php
/**
 * Component: Project / MetaData
 * Purposes:
 * - File to contain all Exceptions types in Project / Metadata
 * @author Miguel Àngel Lozano Márquez <mlozan54@ioc.cat>
 */
if (!defined('DOKU_INC')) die();

class MalFormedJSON extends WikiIocModelException {

    public function __construct($code = 5100, $message = "Malformed JSON to decode", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}

class NotAllEntityMandatoryProperties extends WikiIocModelException {

    public function __construct($code = 5110, $message = "Set de propietats de l'entitat és incomplet", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}
class NotAllEntityValidateProperties extends WikiIocModelException {

    public function __construct($code = 5115, $message = "Set de propietats no passen la validació del model", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }
}



class MetaDataNotFound extends WikiIocModelException {

    public function __construct($code = 5070, $message = "No s'han trobat les metadata del projecte", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}


class WrongParams extends WikiIocModelException {

    public function __construct($code = 5130, $message = "Paràmetres incorrectes", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}

class MetaDataNotUpdated extends WikiIocModelException {

    public function __construct($code = 5090, $message = "La persistència no ha pogut actualitzar les metadata", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}

class PersistenceNsNotFound extends WikiIocModelException {

    public function __construct($code = 5120, $message = "No existeix el namespace", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}

class ClassDaoNotFound extends WikiIocModelException {

    public function __construct($code = 5060, $message = "No s'ha trobat cap classe DAO", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}
class ClassEntityNotFound extends WikiIocModelException {

    public function __construct($code = 5080, $message = "No s'ha trobat cap classe Entity", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}

class ClassRenderNotFound extends WikiIocModelException {

    public function __construct($code = 5030, $message = "No s'ha trobat cap classe Render", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}

class ClassProjectsNotFound extends WikiIocModelException {

    public function __construct($code = 5050, $message = "Cap projecte compleix els criteris de cerca", $previous = NULL) {
        parent::__construct($message, $code, $previous);
    }

}
