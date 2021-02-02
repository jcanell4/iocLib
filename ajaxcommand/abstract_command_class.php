<?php
/**
 * Class abstract_command_class: Classe abstracta de la qual hereten els altres commands.
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
if(!defined('DOKU_INC')) die();
require_once(DOKU_INC."inc/events.php");
//include_once(DOKU_INC."inc/inc_ioc/Logger.php"); //USO: Logger::debug($Texto, $NúmError, __LINE__, __FILE__, $level=-1, $append);

abstract class abstract_command_class extends DokuWiki_Plugin {
    const T_BOOLEAN  = "boolean";
    const T_INTEGER  = "integer";
    const T_DOUBLE   = "double";
    const T_FLOAT    = "float";
    const T_STRING   = "string";
    const T_ARRAY    = "array";
    const T_OBJECT   = "object";
    const T_FUNCTION = "function";
    const T_METHOD   = "method";
    const T_FILE     = "file";
    const T_ARRAY_KEY    = "array_key";
    const T_INTEGER_KEY  = "integer_key";
    const T_BOOLEAN_KEY  = "boolean_key";
    const T_DOUBLE_KEY   = "double_key";
    const T_FLOAT_KEY    = "float_key";
    const T_STRING_KEY   = "string_key";

    const T_JSON         = "json";


    protected static $PLUGUIN_TYPE = 'command';
    protected static $FILENAME_PARAM = 'name';
    protected static $FILE_TYPE_PARAM = 'type';
    protected static $ERROR_PARAM = 'error';
    protected static $FILE_CONTENT_PARAM = 'tmp_name';

    protected $responseHandler = NULL;
    protected $errorHandler = NULL;

    protected $params = array();
    protected $types = array();
    protected $permissionFor = array();
    protected $authenticatedUsersOnly = TRUE;
    protected $runPreprocess = FALSE;

    protected $authorization;
    protected $modelAdapter;
    protected $modelManager;

    protected $needMediaInfo = FALSE;
    protected $throwsEventResponse = TRUE;

    public $error = FALSE;
    public $errorMessage = '';

    protected $defaultFormat = 'undefined';

    public function __construct( $modelAdapter=NULL, $authorization=NULL ) {
        $this->modelAdapter  = $modelAdapter;
        $this->authorization = $authorization;
    }

    /**
     * Constructor en el que s'assigna un nou DokuModelAdapter a la classe
     */
    public function init( $modelManager = NULL ) {
        global $plugin_controller;

        if ($this->params[AjaxKeys::PROJECT_TYPE]) {
            $plugin_controller->setCurrentProject([AjaxKeys::KEY_ID              => $this->params[AjaxKeys::KEY_ID],
                                                   AjaxKeys::PROJECT_TYPE        => $this->params[AjaxKeys::PROJECT_TYPE],
                                                   AjaxKeys::PROJECT_SOURCE_TYPE => $this->params[AjaxKeys::PROJECT_SOURCE_TYPE],
                                                   AjaxKeys::PROJECT_OWNER       => $this->params[AjaxKeys::PROJECT_OWNER],
                                                   AjaxKeys::METADATA_SUBSET     => $this->params[AjaxKeys::METADATA_SUBSET],
                                                   AjaxKeys::KEY_ACTION          => $this->params[AjaxKeys::KEY_ACTION]
                                                ]);
        }

        if (!$modelManager) {
            $modelManager = AbstractModelManager::Instance($this->params[AjaxKeys::PROJECT_TYPE]);  //mirar per què es només s'envia el project type i no la resta de partàmetres?
        }

        $plugin_controller->setPersistenceEngine($modelManager->getPersistenceEngine());

        $this->setModelManager($modelManager);
    }

    /**
     * Estableix l'adaptador a emprar i l'autorització que li correspon.
     * @param modelManager
     */
    public function setModelManager($modelManager) {
        $this->modelManager = $modelManager;

        if (!$this->getModelAdapter()) {
            $this->modelAdapter = $modelManager->getModelAdapterManager();
        }
        if (!$this->authorization) {
            $this->authorization = $modelManager->getAuthorizationManager($this->getAuthorizationType());
        }
    }

    public function getModelManager() {
        return $this->modelManager;
    }

    public function getModelAdapter() {
        return $this->modelAdapter;
    }

    public function getAuthorization() {
        return $this->authorization;
    }

    /**
     * Obtiene la persistencia, correspondiente (por proyecto) a su DokuModelManager, de AbstractModelManager
     */
    public function getPersistenceEngine() {
        return $this->getModelManager()->getPersistenceEngine();
    }

    /**
     * @return string (nom del command a partir del nom de la clase)
     */
    public function getAuthorizationType() {
        return $this->getCommandName();
    }

    public function getParams($key=NULL) {
        return ($key) ? $this->params[$key] : $this->params;
    }

    public function getTypes() {
        return $this->types;
    }

    public function getRunPreprocess() {
        return $this->runPreprocess;
    }

    public function getPermissionFor() {
        return $this->permissionFor;
    }

    protected function setPermissionFor($permissionFor) {
        $this->permissionFor = $permissionFor;
    }

    public function getAuthenticatedUsersOnly() {
        return $this->authenticatedUsersOnly;
    }

    /**
     * @param AbstractResponseHandler $respHand
     */
    public function setResponseHandler($respHand) {
        $this->responseHandler = $respHand;
        if (!$respHand->getModelAdapter()){
            $respHand->setModelAdapter($this->getModelAdapter());
        }
        if (!$respHand->getModelManager()){
            $respHand->setModelManager($this->getModelManager());
        }
    }

    /**
     * @return AbstractResponseHandler
     */
    public function getResponseHandler() {
        return $this->responseHandler;
    }

    public function setErrorHandler($errorHand) {
        $this->errorHandler = $errorHand;
    }

    public function getErrorHandler() {
        return $this->errorHandler;
    }

    /**
     * @param bool $onoff
     */
    public function setThrowsException($onoff) {
        $this->throwsException = $onoff;
    }

    /**
     * @param string[] $types
     */
    protected function setParameterTypes($types) {
        $this->types = $types;
    }

    /**
     * @param string[] $defaultValue
     */
    protected function setParameterDefaultValues($defaultValue) {
        $this->setParameters($defaultValue);
    }

    /**
     * @param string[] $params hash amb els paràmetres
     */
    public function setParameters($params) {
        foreach($params as $key => $value) {
            if(isset($this->types[$key])
                    && $this->types[$key]==self::T_BOOLEAN){
                $value =  filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }else if(isset($this->types[$key])
                    && $this->types[$key]==self::T_ARRAY_KEY){
                $value = key($value);
            }else if(isset($this->types[$key])
                    && $this->types[$key]!= self::T_OBJECT
                    && $this->types[$key]!= self::T_ARRAY
                    && $this->types[$key]!= self::T_FUNCTION
                    && $this->types[$key]!= self::T_METHOD
                    && $this->types[$key]!= self::T_FILE
                    && $this->types[$key]!= self::T_JSON
                    && gettype($value) != $this->types[$key]) {
                settype($value, $this->types[$key]);
            }else if(isset($this->types[$key])
                    && $this->types[$key]== self::T_ARRAY
                    && gettype($value) == self::T_STRING){
                $value = explode(',', $value);
            }else if(isset($this->types[$key])
                    && $this->types[$key]== self::T_OBJECT
                    && gettype($value) == self::T_STRING){
                $value = json_decode($value);
            }else if(isset($this->types[$key])
                    && ($this->types[$key]== self::T_FUNCTION
                        || $this->types[$key]== self::T_METHOD)
                    && gettype($value) != self::T_STRING){
                settype($value, self::T_STRING);
            }else if(isset($this->types[$key])
                    && $this->types[$key]== self::T_FILE
                    && gettype($value) != self::T_ARRAY){
                settype($value, self::T_ARRAY);
            }else if(isset($this->types[$key])
                && $this->types[$key]== self::T_JSON
                && gettype($value) == self::T_STRING) {
                $value = json_decode($value, true);
            }

            $this->params[$key] = $value;
        }
    }

    /**
     * Comproba si la comanda la pot executar tothom i si no es així si s'ha verificat el token de seguretat,
     * si l'usuari està autenticat i si està autoritzat per fer corre la comanda. Si es aixi la executa i en cas
     * contrari llença una excepció.
     *
     * @return string|null resposta de executar el command en format JSON
     * @throws Exception si no es té autorització
     */
    public function run() {
        $ret = NULL;
        $this->triggerStartEvents();
        $this->authorization->setPermission($this);
        $retAuth = $this->authorization->canRun();
        if ($retAuth) {
            $ret = $this->getResponse();
        } else {
            $responseGenerator = new AjaxCmdResponseGenerator();
            $e = $this->authorization->getAuthorizationError(AuthorizationKeys::EXCEPTION_KEY);
            $p = $this->authorization->getAuthorizationError(AuthorizationKeys::EXTRA_PARAM_KEY);
            if($p){
                $this->handleError(new $e($p), $responseGenerator);
            }else{
                $this->handleError(new $e(), $responseGenerator);
            }
            $ret = $responseGenerator->getJsonResponse();
        }
        //for a dojo iframe the json response has to be inside a textarea
        if (isset($this->params['iframe'])) {
            $ret = "<html><body><textarea>" . $ret. "</textarea></body></html>";
        }
        $this->triggerEndEvents();
        return $ret;
    }

    protected function triggerStartEvents() {
        $cn = $this->getCommandName();
        $tmp = array();
        $evt = new Doku_Event("WIOC_AJAX_COMMAND_".$cn, $tmp);
        $evt->advise_before();
        unset($evt);
        $tmp = array("call" => $cn);
        $evt = new Doku_Event("WIOC_AJAX_COMMAND", $tmp);
        $evt->advise_before();
        unset($evt);
    }

    protected function triggerEndEvents() {
        $cn = $this->getCommandName();
        $tmp = array();
        $evt = new Doku_Event("WIOC_AJAX_COMMAND_".$cn, $tmp);
        $evt->advise_after();
        unset($evt);
        $tmp = array("call" => $cn);
        $evt = new Doku_Event("WIOC_AJAX_COMMAND", $tmp);
        $evt->advise_after();
        unset($evt);
    }

    protected function handleError($e, &$responseGenerator){
        if ($e->getCode() >= 1000){
            $error_handler = $this->getErrorHandler();
            if ($error_handler) {
                $error_handler->processResponse($this->params, $e, $responseGenerator);
            } else {
                $this->getDefaultErrorResponse($this->params, $e, $responseGenerator);
            }
        }else{
            $this->getDefaultErrorResponse($this->params, $e, $responseGenerator);
        }
    }

    /**
     * Processa la comanda, si hi ha un ResponseHandler se li passen els paràmetres, la resposta i el
     * AjaxCmdResponseGenerator, si no hi ha es pasa es crida al métode per obtenir la resposta per defecte amb el
     * AjaxCmdResponseGenerator i la resposta.
     * La resposta es passa per referencia, de manera que es modificada als métodes processResponse/getDefaultResponse.
     *
     * @return string resposta processada en format JSON
     */
    protected function getResponse() {
        $ret = new AjaxCmdResponseGenerator();
        try {
            $response = $this->process();
            $response_handler = $this->getResponseHandler();

            if ($response_handler) {
                $response_handler->setPermission($this->authorization->getPermission());
                $response_handler->processResponse($this->params, $response, $ret);
            } else {
                if($this->throwsEventResponse){
                    $this->preResponse($ret);
                }
                $this->getDefaultResponse($response, $ret);
                if($this->throwsEventResponse){
                    $this->postResponse($response, $ret);
                }
            }
        } catch (HttpErrorCodeException $e){
            $this->error        = $e->getCode();
            $this->errorMessage = $e->getMessage();
            return $this->errorMessage;
        } catch (Exception $e){
            $this->handleError($e, $ret);
        }
        $jsonResponse = $ret->getJsonResponse();
        return $jsonResponse;
    }

    /**
     * Retorna la resposta per defecte del command.
     *
     * @param mixed                    $response
     * @param AjaxCmdResponseGenerator $responseGenerator
     *
     * @return mixed
     */
    abstract protected function getDefaultResponse($response, &$responseGenerator);

    /**
     * Retorna la resposta per defecte quan el process llença una excepció.
     * Aquest mètode s'executarà només en cas que la comanda no disposi de cap
     * objecte errorHandler (de tipus ResponseHandler).
     *
     * @param Exception                $response
     * @param AjaxCmdResponseGenerator $responseGenerator
     *
     * @return mixed
     */
    public function getDefaultErrorResponse($params, $e, &$ret){
        $ret->addError($e->getCode(), $e->getMessage());
    }

    /**
     * Retorna si cal carregar la informació incloent dades de media.
     *
     * @return string
     */
    public function getNeedMediaInfo() {
        return $this->needMediaInfo;
    }

    /**
     * Retorna el tipus de plugin.
     *
     * @return string
     */
    public function getPluginType() {
        return self::$PLUGUIN_TYPE;
    }

    /**
     * Retorna el nom del plugin.
     *
     * @return string
     */
    public function getPluginName() {
        $dirPlugin = realpath($this->getClassDirName() . '/../..');
        if($dirPlugin) {
            $dir = substr($dirPlugin, -11);
            if($dir && $dir === "ajaxcommand") {
                $ret = "ajaxcommand";
            } else {
                $ret = parent::getPluginName();
            }
        } else {
            $ret = parent::getPluginName();
        }
        return $ret;
    }

    /**
     * Retorna el nom del component.
     *
     * @return string
     */
    public function getPluginComponent() {
        $dirs   = explode("/", $this->getClassDirName());
        $length = sizeof($dirs);
        if($length > 2) {
            $dir = substr($dirs[$length - 3], -11);
            if($dir && $dir === "ajaxcommand") {
                $ret = $dirs[$length - 1];
            } else {
                $ret = parent::getPluginName();
            }
        } else {
            $ret = parent::getPluginName();
        }
        return $ret;
    }


    public function getJsInfo(){
        return WikiIocInfoManager::getJsInfo();
    }

    /**
     * Retorna el nom del directori on es troba la classe.
     *
     * @return string
     */
    private function getClassDirName() {
        $thisClass = new ReflectionClass($this);
        return dirname($thisClass->getFileName());
    }

    /**
     * Processa el command.
     * @return mixed varia segons la implementació del command
     */
    abstract protected function process();

    protected function postResponse($responseData, &$ajaxCmdResponseGenerator) {
        $data = $this->_getDataEvent($ajaxCmdResponseGenerator, $responseData);
        $evt = new Doku_Event("WIOC_PROCESS_RESPONSE", $data);
        $evt->advise_after();
        unset($evt);
        $evt = new Doku_Event("WIOC_PROCESS_RESPONSE_".$this->getCommandName(), $data);
        $evt->advise_after();
        unset($evt);
        $ajaxCmdResponseGenerator->addSetJsInfo($this->getJsInfo());

        //[NOTA: Rafael] Considero que este código ya no es necesario
        if ($this->params[AjaxKeys::PROJECT_TYPE]) {
            if (!$responseData[ProjectKeys::KEY_PROJECT_EXTRADATA][AjaxKeys::PROJECT_TYPE]) { //es una página de un proyecto
                $ajaxCmdResponseGenerator->addExtraContentStateResponse($responseData[AjaxKeys::KEY_ID], AjaxKeys::PROJECT_TYPE, $this->params[AjaxKeys::PROJECT_TYPE]);
            }
        } else if ($data['command'] !== 'notify') {
            if(isset($responseData[AjaxKeys::KEY_ID])){
                $ajaxCmdResponseGenerator->addExtraContentStateResponse($responseData[AjaxKeys::KEY_ID], AjaxKeys::FORMAT, $this->getFormat());
            }
        }

        if ($this->params[ProjectKeys::PROJECT_OWNER]) {
            $ajaxCmdResponseGenerator->addExtraContentStateResponse($responseData[AjaxKeys::KEY_ID], ProjectKeys::PROJECT_OWNER, $this->params[ProjectKeys::PROJECT_OWNER]);
            $ajaxCmdResponseGenerator->addExtraContentStateResponse($responseData[AjaxKeys::KEY_ID], ProjectKeys::PROJECT_SOURCE_TYPE, $this->params[ProjectKeys::PROJECT_SOURCE_TYPE]);
        }
        if ($responseData[AjaxKeys::KEY_ACTIVA_FTP_PROJECT_BTN]){
            $ajaxCmdResponseGenerator->addExtraContentStateResponse($responseData[AjaxKeys::KEY_ID], AjaxKeys::KEY_FTP_PROJECT_BUTTON, $responseData[AjaxKeys::KEY_ACTIVA_FTP_PROJECT_BTN]);
        }

        if ($responseData[AjaxKeys::KEY_ACTIVA_FTPSEND_BTN]){
            $ajaxCmdResponseGenerator->addExtraContentStateResponse($responseData[AjaxKeys::KEY_ID], AjaxKeys::KEY_FTPSEND_BUTTON, $responseData[AjaxKeys::KEY_ACTIVA_FTPSEND_BTN]);
        }

        if ($responseData[ProjectKeys::KEY_EXTRA_STATE]) {
            $stateId = $responseData[ProjectKeys::KEY_EXTRA_STATE][ProjectKeys::KEY_EXTRA_STATE_ID];
            $stateValue = $responseData[ProjectKeys::KEY_EXTRA_STATE][ProjectKeys::KEY_EXTRA_STATE_VALUE];
            $ajaxCmdResponseGenerator->addExtraContentStateResponse($responseData[ProjectKeys::KEY_ID], $stateId, $stateValue);
        }

        if ($responseData['user_state']) {
            $ajaxCmdResponseGenerator->addUserState($responseData['user_state']);
        }
    }

    protected function preResponse(&$ajaxCmdResponseGenerator) {
        $data = $this->_getDataEvent($ajaxCmdResponseGenerator);
        $evt = new Doku_Event("WIOC_PROCESS_RESPONSE", $data);
        $ret = $evt->advise_before();
        unset($evt);
        $evt = new Doku_Event("WIOC_PROCESS_RESPONSE_".$this->getCommandName(), $data);
        $ret = $ret.$evt->advise_before();
        unset($evt);
        return $ret;
    }

    private function _getDataEvent(&$ajaxCmdResponseGenerator, $responseData=NULL){
        $ret = array(
            "command" => $this->getCommandName(),
            "requestParams" => $this->params,
            "responseData" => $responseData,
            "ajaxCmdResponseGenerator" => $ajaxCmdResponseGenerator,
        );
        return $ret;
    }

    private function getCommandName() {
        return preg_replace('/_command$/', '', get_class($this));
    }

    //sobreescribe el método de abstract_project_command_class para el caso en que no exista $dataProject,
    //por ejemplo, cuando proviene de abstract_rest_command_class
    public function getKeyDataProject() {
        return NULL;
    }

    protected function getFormat(){
        return IocCommon::getFormat($this->params[PageKeys::KEY_ID], $this->defaultFormat);
    }
}
