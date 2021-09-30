<?php
/**
 * AbstractWikiAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

abstract class AbstractWikiAction {

    static protected $flagMainAction = NULL; //control de Actions en cascada
    protected $params;
    protected $modelManager;

    public function __construct($params=NULL) {
        if ($params) $this->params = $params;
    }

    public function __destruct() {
        if (get_class($this) === self::$flagMainAction) {
            self::$flagMainAction = NULL;
        }
    }
    
    public function getActionInstance($actionName, $params=NULL, $noInit=TRUE){
        $action = $this->modelManager->getActionInstance($actionName, $params, $noInit);
        $action->params = $this->params;
        $action->modelManager = $this->modelManager;
        return $action;
    }

    public function init($modelManager = NULL) {
        $this->modelManager = $modelManager;
        self::$flagMainAction = (!self::$flagMainAction) ? get_class($this) : self::$flagMainAction;
    }

    public function get($paramsArr = array()){
        //previene que un Action llamado por otro Action vuelva a ejecutar los trigers
        $jomateix = (get_class($this) === self::$flagMainAction);

        if ($jomateix)
            $this->triggerStartEvents();
        if (!empty($paramsArr))
            $this->setParams($paramsArr);
        $this->preResponseProcess();
        $ret = $this->responseProcess();
        $this->postResponseProcess($ret);
        if ($jomateix)
            $this->triggerEndEvents();
        return $ret;
    }

    public function getModelManager() {
        return $this->modelManager;
    }

    public static function generateInfo($type, $message, $id='', $duration=-1, $subSet=NULL) {
        return IocCommon::generateInfo($type, $message, $id, $duration, $subSet);
    }
    
    protected static function addInfoToInfo( $infoA, $infoB ) {
        return IocCommon::addInfoToInfo($infoA, $infoB);
    }

    protected function triggerStartEvents() {
        $tmp= array(); //NO DATA
        trigger_event( 'WIOC_AJAX_COMMAND_STARTED', $tmp);
        if(!empty($tmp)){
            $this->preResponseTmp[] = $tmp;
        }
    }

    protected function triggerEndEvents() {
        $tmp = array(); //NO DATA
        trigger_event( 'WIOC_AJAX_COMMAND_DONE', $tmp );
        if(!empty($tmp)){
            $this->postResponseTmp[] = $tmp;
        }
    }

    protected function setParams($paramsArr){
        if(is_array($this->params)){
            $this->params = array_merge($this->params, $paramsArr);
        }else{
            $this->params = $paramsArr;
        }
    }

    protected function idToRequestId($requestId) {
        return str_replace(":", "_", $requestId);
    }

    protected function preResponseProcess() {
    }

    protected abstract function responseProcess();

    protected function postResponseProcess(&$ret) {
        return $ret;
    }
    
    protected function addNotificationsMetaToResponse(&$response, $ns, $rev, $list, $extraCallerParams=false) {
        if (!isset($response['meta'])) {
            $response['meta'] = array();
        }

        //Descomponer la lista de usuarios en usuarios únicos
        foreach ($list as $elem) {
            $users = preg_split("/[\s,]+/", $elem['username']);
            foreach ($users as $user) {
                $u_list[] = $user;
            }
        }
        $u_list = array_unique($u_list);
        foreach ($u_list as $user) {
            $users_list[] = ['username' => $user,
                             'name' => ""];
        }
        
        $response['meta'][] = [
            "id" => $ns . "_metaNotifications",
            "title" => WikiIocLangManager::getLang('notification_form_title'),
            "content" => [
                'action' => 'lib/exe/ioc_ajax.php',
                'method' => 'post',
                'fields' => [
                    [
                        'type' => 'hidden',
                        'name' => 'call',
                        'value' => 'notify',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'do',
                        'value' => 'add_message',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'type',
                        'value' => 'warning',
                    ],
                    [
                        'type' => 'amd',
                        'data' => [
                            'ns' => $ns,
                            'buttonLabel' => WikiIocLangManager::getLang('search'),
                            'fieldName' => 'to',
                            'token' => getSecurityToken(),
                            'searchDataUrl' => 'lib/exe/ioc_ajax.php?call=user_list', //
                            'dialogTitle' => 'Cerca usuaris per afegir', // TODO: localitzar
                            'dialogButtonLabel' => 'Afegir', // TODO[Xavi] Localitzar
                            'fields' => [
                                'username' => 'Nom d\'usuari', // TODO: Localitzar
                                'name' => 'Nom' // TODO: Localitzar
                            ],
                            'fieldId' => 'username',
                            'defaultEntryField' => 'name',
                            'data' => $users_list,
                        ],
                        'class' => 'IocFilteredList',
                        'label' => WikiIocLangManager::getLang('notification_form_to'), // Optional
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'id',
                        'value' => $ns,
                        'label' => sprintf(WikiIocLangManager::getLang('notification_form_check_add_id'), $response['id']), // Optional
                        'properties' => ['checked'] // Optional
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'rev',
                        'value' => $rev,
                    ],
                    [
                        'type' => 'checkbox',
                        'name' => 'send_email',
                        'value' => true,
                        'label' => WikiIocLangManager::getLang('notification_form_check_add_email'), // Optional
                        'properties' => ['checked'] // Optional
                    ],
                    [
                        'type' => 'textarea',
                        'name' => 'message',
                        'value' => '',
                        'label' => WikiIocLangManager::getLang('notification_form_message'), // Optional
                        'properties' => ['required'] // Optional
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'data-call',
                        'value' => $extraCallerParams,
                    ]

                ],
                'send_button' => WikiIocLangManager::getLang('notification_form_button_send')
            ],

            "type" => "request_form" // aixó no se si es necessari
        ];

    }
}
