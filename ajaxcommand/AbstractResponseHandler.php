<?php
/**
 * Class AbstractResponseHandler
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
if(!defined("DOKU_INC")) die();

abstract class AbstractResponseHandler {
    const LOGIN  = 'login';
    const PAGE   = 'page';
    const EDIT   = 'edit';
    const CANCEL = 'cancel';
    const SAVE   = 'save';
    const MEDIA  = 'media';
    const MEDIADETAILS  = 'mediadetails';
    const ADMIN_TASK  = 'admin_task';
    const ADMIN_TAB  = 'admin_tab';
    const PRINT_ACTION  = 'print';
    const PREVIEW_ACTION  = 'preview';
    const PROJECT  = 'project';

    private $cmd;
    private $modelAdapter;
    private $modelManager;
    private $permission;

    /**
     * Constructor al que se li passa el nom del Command com argument.
     * @param string $cmd
     * @param instance $modelAdapter
     * @param instance $permission
     */
    public function __construct($cmd) {
        $this->cmd = $cmd;
    }

    /**
     * @return string
     */
    public function getCommandName() {
        return $this->cmd;
    }

    public function getModelManager() {
        return $this->modelManager;
    }

    public function setModelManager($modelManager) {
        $this->modelManager = $modelManager;
    }

    /**
     * @return ModelAdapter instance
     */
    public function getModelAdapter() {
        return $this->modelAdapter;
    }

    /**
     * Set ModelAdapter instance
     */
    public function setModelAdapter($modelAdapter) {
        $this->modelAdapter = $modelAdapter;
    }

    /**
     * @return Permission instance
     */
    public function getPermission() {
        return $this->permission;
    }

    /**
     * Set Permission instance
     */
    public function setPermission($permission) {
        $this->permission = $permission;
    }

    public function getJsInfo(){
        return WikiIocInfoManager::getJsInfo();
    }

    /**
     * Processa la resposta cridant abans a preResponse() i després de processar-la a postResponse().
     * @param string[]                 $requestParams hash amb els paràmetres
     * @param mixed                    $responseData  dades per processar
     * @param AjaxCmdResponseGenerator $ajaxCmdResponseGenerator
     */
    public function processResponse($requestParams, $responseData, &$ajaxCmdResponseGenerator) {
        $this->preResponse($requestParams, $ajaxCmdResponseGenerator);
        $this->response($requestParams, $responseData, $ajaxCmdResponseGenerator);
        $this->postResponse($requestParams, $responseData, $ajaxCmdResponseGenerator);
    }

    /**
     * Codi per executar quan es processa la resposta.
     * @param string[]                 $requestParams hash amb els paràmetres
     * @param mixed                    $responseData  dades per processar
     * @param AjaxCmdResponseGenerator $ajaxCmdResponseGenerator
     * @return mixed
     */
    protected abstract function response($requestParams, $responseData, &$ajaxCmdResponseGenerator);

    /**
     * Codi per executar abans de processar la resposta.
     * @param string[]                 $requestParams hash amb els paràmetres
     * @param AjaxCmdResponseGenerator $ajaxCmdResponseGenerator
     * @return mixed
     */
    protected abstract function preResponse($requestParams,  &$ajaxCmdResponseGenerator);

    /**
     * Codi per executar despres de processar la resposta.
     * @param string[]                 $requestParams hash amb els paràmetres
     * @param mixed                    $responseData  dades per processar
     * @param AjaxCmdResponseGenerator $ajaxCmdResponseGenerator
     * @return mixed
     */
    protected abstract function postResponse($requestParams, $responseData,  &$ajaxCmdResponseGenerator);

}
