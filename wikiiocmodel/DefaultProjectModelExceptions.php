<?php
/**
 * WikiIocModelException: Excepciones del proyecto 'defaultProject'
 *
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
if (!defined('DOKU_INC')) die();
require_once(DOKU_INC . 'inc/common.php');

class ImageNotFoundException extends WikiIocModelException {
    public function __construct($image, $codeMessage = "imageNotFound", $code=7107, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $image);
    }
}

class PageNotFoundException extends WikiIocModelException {
    public function __construct($page, $codeMessage = "pageNotFound", $code=7101, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $page);
    }
}

class PageAlreadyExistsException extends WikiIocModelException {
    public function __construct($page, $message='pageExist', $code=7102, $previous=NULL) {
        parent::__construct($message, $code, $previous, $page);
    }
}

class DateConflictSavingException extends WikiIocModelException {
    public function __construct($page, $codeMessage = "conflictsSaving", $code=7103, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $page);
    }
}

class WordBlockedException extends WikiIocModelException {
    public function __construct($page, $codeMessage = "wordblock", $code=7104, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $page);
    }
}

class CommandAuthorizationNotFound extends WikiIocModelException {
    public function __construct($param='', $message='commandAuthorizationNotFound', $code=7105, $previous=NULL) {
        parent::__construct($message, $code, $previous, $param);
    }
}

class InsufficientPermissionToUploadMediaException extends WikiIocModelException {
    public function __construct($param='', $codeMessage = 'auth_UploadMedia', $code=7106, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $param);
    }
}

class FailToUploadMediaException extends WikiIocModelException {
    public function __construct($errorCode, $codeMessage = 'uploadfail', $code=7108, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $errorCode);
    }
}

class MaxSizeExcededToUploadMediaException extends WikiIocModelException {
    public function __construct($param='', $codeMessage = 'auth_UploadMedia', $code=7109, $previous=NULL) {
        if(!$codeMessage){
            $codeMessage = sprintf(WikiIocLangManager::getLang('uploadsize'),
                    filesize_h(php_to_byte(ini_get('upload_max_filesize'))));
        }
        parent::__construct($codeMessage, $code, $previous, $param);
    }
}

class InvalidUserException extends WikiIocModelException {
    public function __construct($user, $message='Aquest usuari no és vàlid.', $code=7110, $previous=NULL) {
        parent::__construct($message, $code, $previous, $user);
    }
}

class IncorrectParamsException extends WikiIocModelException {
    public function __construct($message='Paràmetres incorrectes.', $code=7111, $previous=NULL) {
        parent::__construct($message, $code, $previous);
    }
}

class PartialEditNotSupportedException extends WikiIocModelException {
    public function __construct($param='', $message="No es pot utilizar el codi ~~USE:WIOCCL~~ en una edició parcial, s'ha de fer servir en una edició completa.", $code=7112, $previous=NULL) {
        parent::__construct($message, $code, $previous, $param);
    }
}

class UnimplementedTranslatorException extends WikiIocModelException {
    public function __construct($message='Traductor no implementat.', $code=7113, $previous=NULL) {
        parent::__construct($message, $code, $previous);
    }
}

class WrongClosingTranslatorException extends WikiIocModelException {
    public function __construct($param='', $message='Tancament d\'etiquetes incorrecte, revisa l\'apertura i tancament de %s. S\'ha trobat l\'etiqueta %s', $code=7114, $previous=NULL) {
        parent::__construct($message, $code, $previous, $param);
    }
}

class MissingClosingTranslatorException extends WikiIocModelException {
    public function __construct($param='', $message='Tancament d\'etiquetes descompensat des de l\'etiqueta %s.', $code=7115, $previous=NULL) {
        parent::__construct($message, $code, $previous, $param);
    }
}

class DefaultProjectAlreadyExistsException extends WikiIocModelException {
    public function __construct($page, $message='defaultProjectAlreadyExists', $code=7116, $previous=NULL) {
        parent::__construct($message, $code, $previous, $page);
    }
}

class PageIsProtectedCantEditException extends WikiIocModelException {
    public function __construct($page, $message='La pàgina està protegida i no es pot editar', $code=7102, $previous=NULL) {
        parent::__construct($message, $code, $previous, $page);
    }
}

class InconsistentDataException extends WikiIocModelException{
    public function __construct($explanation, $message='Les dades són inconsistents: %s', $code=7117, $previous=NULL) {
        parent::__construct($message, $code, $previous, $explanation);
    }
}


