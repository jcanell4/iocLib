<?php
/**
 * Define las clases de excepciones
 *
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
require_once (DOKU_INC . 'inc/inc_ioc/Logger.php');

abstract class WikiIocModelException extends Exception {
    public function __construct($codeMessage, $code, $previous=NULL, $target=NULL, $codeProject=NULL) {
        if ($codeProject !== NULL)
            $mess = WikiIocLangManager::getLang($codeProject);
        else
            $mess = WikiIocLangManager::getLang($codeMessage);

        $message = (is_array($mess)) ? $mess[$codeMessage] : $mess;
        if ($message === NULL) $message = $codeMessage;

        if ($target) {
            if(is_array($target)){
                $message = vsprintf($message, $target);
            }else{
                $message = sprintf($message, $target);
            }
        }
        //Logger::debug("Params, codemessage: $codeMessage message: $message code: $code, previous: $previous, target: $target", 0, 0, "", 1, FALSE);
        parent::__construct($message, $code, $previous);
    }
}

class HttpErrorCodeException extends WikiIocModelException {
    public function __construct($message, $code, $previous=NULL) {
        parent::__construct($message, $code, $previous);
    }
}

class UnavailableMethodExecutionException extends WikiIocModelException {
    public function __construct($method, $message="Unavailable method %s", $code=9001, $previous=NULL) {
        parent::__construct($message, $code, $previous, $method);
    }
}

class UnknownMimeTypeException extends WikiIocModelException {
    public function __construct($message="El format del fitxer no és un tipus mime reconegut.", $code=9002) {
        parent::__construct($message, $code, NULL);
    }
}

class AuthorizationNotTokenVerified extends WikiIocModelException {
    public function __construct($codeMessage='auth_TokenNotVerified', $code=9020, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous);
    }
}

class AuthorizationNotUserAuthenticated extends WikiIocModelException {
    public function __construct($codeMessage='auth_UserNotAuthenticated', $code=9021, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous);
    }
}
class AuthorizationNotCommandAllowed extends WikiIocModelException {
    public function __construct($target=NULL) {
        $codeMessage="auth_CommadNotAllowed";
        $code=9022;
        $previous=NULL;
        parent::__construct($codeMessage, $code, $previous, $target);
    }
}
class FileIsLockedException extends WikiIocModelException {
    public function __construct($id="", $codeMessage="lockedByAlert", $code=9023, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $id);
    }
}

class DraftNotFoundException extends WikiIocModelException {
    public function __construct($id="", $codeMessage='DraftNotFoundException', $code=9024, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $id);
    }
}

class UnexpectedLockCodeException extends WikiIocModelException {
    public function __construct($id="", $codeMessage='UnexpectedLockCode', $code=9025, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $id);
    }
}

class UnknownUserException extends WikiIocModelException {
    public function __construct($user, $codeMessage='UnknownUser', $code=9026, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $user);
    }
}

class IncorrectParametersException extends WikiIocModelException {
    public function __construct($codeMessage='IncorrectParameters', $code=9027, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous);
    }
}

class ClassNotFoundException extends WikiIocModelException {
    public function __construct($class, $codeMessage='ClassNotFound', $code=9028, $previous=NULL) {
        parent::__construct($codeMessage, $code, $previous, $class);
    }
}

class IllegalCallExeption extends WikiIocModelException {
    public function __construct($message, $code=9029, $previous=NULL) {
        $message = "No està permés cridar aquest mètode en aquest context: $message";
        parent::__construct($message, $code, $previous);
    }
}

class CantCreatePageInProjectException extends WikiIocModelException {
    public function __construct($param='', $message='cantCreatePageInProject', $code=9030, $previous=NULL) {
        parent::__construct($message, $code, $previous, $param);
    }
}

/**
 * Excepciones propias de los proyectos
 */
abstract class WikiIocProjectException extends WikiIocModelException {
    public function __construct($codeMessage, $code, $target=NULL, $previous=NULL, $project=NULL) {
        parent::__construct($codeMessage, $code, $previous, $target, $project);
    }
}

class InsufficientPermissionToCreatePageException extends WikiIocProjectException {
    public function __construct($page, $codeMessage='auth_CreatePage', $code=7001) {
        parent::__construct($codeMessage, $code, $page);
    }
}

class InsufficientPermissionToViewPageException extends WikiIocProjectException {
    public function __construct($page, $codeMessage='auth_ViewPage', $code=7002) {
        parent::__construct($codeMessage, $code, $page);
    }
}

class InsufficientPermissionToEditPageException extends WikiIocProjectException {
    public function __construct($page, $codeMessage='auth_EditPage', $code=7003) {
        parent::__construct($codeMessage, $code, $page);
    }
}

class InsufficientPermissionToWritePageException extends WikiIocProjectException {
    public function __construct($page, $codeMessage='auth_WritePage', $code=7004) {
        parent::__construct($codeMessage, $code, $page);
    }
}

class InsufficientPermissionToDeletePageException extends WikiIocProjectException {
    public function __construct($page, $codeMessage='auth_DeletePage', $code=7005) {
        parent::__construct($codeMessage, $code, $page);
    }
}

class InsufficientPermissionToDeleteResourceException extends WikiIocProjectException {
    public function __construct($page, $codeMessage='auth_DeleteResource', $code=7006) {
        parent::__construct($codeMessage, $code, $page);
    }
}

class UnknownPojectTypeException extends WikiIocProjectException {
    public function __construct($page, $codeMessage='UnknownPojectType', $code=7007) {
        parent::__construct($codeMessage, $code, $page);
    }
}

class NotAllowedPojectCommandException extends WikiIocProjectException {
    public function __construct($page, $codeMessage='NotAllowedPojectCommandException', $code=7008) {
        parent::__construct($codeMessage, $code, $page);
    }
}

class WsMoodleCalendarException extends WikiIocModelException {
    public function __construct($exception=NULL, $message="S'ha produït una excepció de tipus '%s' fent una crida al servei de gestió d'esdeveniments de moodle amb el missatge: %s", $code=7009, $previous=NULL) {
        $targ = array($exception->errorcode, $exception->message);
        parent::__construct($message, $code, $previous, $targ);
    }
}

class WsMoodleInvalidCourseIdException extends WikiIocModelException {
    public function __construct($message="No es pot envir dades a moodle. El codi del curs és obligatori: %s", $code=7010, $previous=NULL) {
        parent::__construct($message, $code, $previous);
    }
}

class ConfigurationProjectNotAvailableException extends WikiIocProjectException {
    public function __construct($page, $message="L'actualització del projecte no disposa del corresponent projecte de configuració", $code=7011) {
        parent::__construct($message, $code, $page);
    }
}

class WsMixException extends WikiIocModelException {
    public function __construct($courseId, $exception=NULL, $message="", $code=7012, $previous=NULL) {
        $targ = array($courseId);
        switch ($exception->errorcode){
            case "invalidtoken":
                $message="No teniu accès a Moodel des de la WIKI. Tanqueu sessió a la WIKI i torneu-vos a connectar";
                break;
            case "errorcoursenotfound":
                $message="No existeix cap curs amb l'identificador %d a Moodle";
                $code += 1;
                break;
            case "invalidcourse":
                $message="No existeix cap curs amb l'identificador %d a Mix";
                $code += 2;
                break;
            case "zerolessons":
                $message="El curs amb l'identificador %d encara no té lliçons definides a MIX";
                $code += 3;
                break;
            case "errorcoursecontextnotvalid":
                $message=$exception->message;
                $code += 4;
                break;
        }
        parent::__construct($message, $code, $previous, $targ);
    }
}

class MoodleTokenNotFoundException extends WikiIocModelException {
    public function __construct($message="No teniu accès a Moodel des de la WIKI. Tanqueu sessió a la WIKI i torneu-vos a connectar", $code=7030, $previous=NULL) {
        parent::__construct($message, $code, $previous);
    }    
}
