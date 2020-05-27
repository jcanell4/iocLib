<?php
/**
 * ProjectModelException: Establece las clases de excepciones generales para proyectos
 * @culpable Rafael Claver
 */
if (!defined('DOKU_INC')) die();

class ProjectExistException extends WikiIocProjectException {
    public function __construct($page, $message='projectExist', $code=7201) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class ProjectNotExistException extends WikiIocProjectException {
    public function __construct($page, $message='projectNotExist', $code=7202) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class UnknownProjectException extends WikiIocProjectException {
    public function __construct($page, $message='unknownProject', $code=7203) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class UserNotAuthorizedException extends WikiIocProjectException {
    public function __construct($page, $message='userNotAuthorized', $code=7204) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class AuthorNotVerifiedException extends WikiIocProjectException {
    public function __construct($page, $message='authorNotVerified', $code=7205) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class ResponsableNotVerifiedException extends WikiIocProjectException {
    public function __construct($page, $message='responsableNotVerified', $code=7206) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class InsufficientPermissionToEditProjectException extends WikiIocProjectException {
    public function __construct($page, $message='insufficientPermissionToEditProject', $code=7207) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class InsufficientPermissionToCreateProjectException extends WikiIocProjectException {
    public function __construct($page, $message='insufficientPermissionToCreateProject', $code=7208) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class InsufficientPermissionToDeleteProjectException extends WikiIocProjectException {
    public function __construct($page, $message='insufficientPermissionToDeleteProject', $code=7209) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class InsufficientPermissionToGenerateProjectException extends WikiIocProjectException {
    public function __construct($page, $message='insufficientPermissionToGenerateProject', $code=7210) {
        parent::__construct("$message$page", $code, $page, NULL, 'projectException');
    }
}

class InsufficientPermissionToFtpProjectException extends WikiIocProjectException {
    public function __construct($page, $message='insufficientPermissionToFtpProject', $code=7211) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class InsufficientPermissionToCommandProjectException extends WikiIocProjectException {
    public function __construct($page, $message='InsufficientPermissionToCommandProjectException', $code=7212) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class InvalidDataProjectException extends WikiIocProjectException{
    private $details;
    public function __construct($nsProject, $details, $message='InvalidDataProjectException', $code=7213) {
        parent::__construct($message, $code, $nsProject, NULL, 'projectException');
        $this->details = $details;
    }
    
    public function getDetails(){
        $ret="";
        if(isset($this->details)){
            $tdet = strtoupper(WikiIocLangManager::getLang("details"))+":\n";
            $ret = $tdet + $this->details;
        }
        return $ret;
    }
    
    public function getMessage(){
        return parent::getMessage() + $this->getDetails();
    }
}

class MissingGroupFormBuilderException extends WikiIocProjectException {
    public function __construct($page='', $message='MissingGroupFormBuilder', $code=7301) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class MissingValueFormBuilderException extends WikiIocProjectException {
    public function __construct($page='', $message='MissingValueFormBuilder', $code=7302) {
        parent::__construct($message, $code, $page, NULL, 'projectException');
    }
}

class WrongNumberOfColumnsFormBuilderException extends WikiIocProjectException {
    public function __construct($page='', $message='nombre incorrecte de', $code=7303) {
        parent::__construct("Has indicat $message columnes i el nombre de columnes admés està entre 1 i 12", $code, $page);
    }
}
