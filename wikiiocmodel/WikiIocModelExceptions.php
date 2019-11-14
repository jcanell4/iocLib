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
        Logger::debug("Params, codemessage: $codeMessage message: $message code: $code, previous: $previous, target: $target", 0, 0, "", 1, FALSE);
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
