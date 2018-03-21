<?php
/**
 * Class AjaxCmdResponseGenerator
 *
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
if (!defined('DOKU_INC')) die();

class AjaxCmdResponseGenerator {

    private $response;

    /**
     * Constructor de la classe on s'instancia el generador de respostes
     */
    public function __construct() {
        $this->response = new ArrayJSonGenerator();
    }

    /**
     * @param JsonGenerator $response
     */
    public function addResponse($response) {
        $this->response->add($response);
    }

    /**
     * Afegeix una resposta amb tipus ERROR_TYPE al generador de respostes.
     * @param string $message missatge a afegir al generador de respostes
     */
    public function addError($c, $m = NULL) {
        if (is_string($c)) {
            $value = array("code" => 0, "message" => $c);
        } else if (isset ($m)) {
            $value = array("code" => $c, "message" => $m);
        } else {
            $value = $c;
        }
        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::ERROR_TYPE, $value)
        );
    }

    /**
     * Afegeix una resposta amb tipus ERROR_TYPE al generador de respostes.
     * @param string $message missatge a afegir al generador de respostes
     */
    public function addAlert($message) {
        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::ALERT_TYPE, $message)
        );
    }

    /**
     * Afegeix una resposta amb tipus TITTLE_TYPE al generador de respostes.
     * @param string $tit títol per afegir al generador de respostes
     */
    public function addTitle($tit) {
        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::TITLE_TYPE, $tit)
        );
    }

    /**
     * Afegeix una resposta amb tipus COMMAND_TYPE::JSINFO al generador de respostes.
     * @param string[] $jsInfo hash amb la informació que es pasarà al JavaScript
     */
    public function addSetJsInfo($jsInfo) {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::JSINFO,
                $jsInfo
            )
        );
    }

    /**
     * Afegeix una resposta amb tipus COMMAND_TYPE::PROCESS_FUNCTION al generador de respostes
     *
     * @param bool $isAmd
     * @param string $processName
     * @param mixed|null $params
     */
    public function addProcessFunction($isAmd, $processName, $params = NULL)
    {
        $resp = array(
            "type" => JSonGenerator::PROCESS_FUNCTION,
            "amd" => $isAmd,
            "processName" => $processName,
        );

        if ($params) {
            $resp["params"] = $params;
        }

        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::COMMAND_TYPE, $resp)
        );
    }

    /**
     * Afegeix una resposta amb tipus COMMAND_TYPE::PROCESS_DOM_FROM_FUNCTION al generador de respostes.
     *
     * @param string $domId
     * @param bool $isAmd
     * @param string $processName
     * @param array $params
     */
    public function addProcessDomFromFunction($domId, $isAmd, $processName, $params = NULL)
    {
        $resp = array(
            "type" => JSonGenerator::PROCESS_DOM_FROM_FUNCTION,
            "id" => $domId,
            "amd" => $isAmd,
            "processName" => $processName,
        );
        if ($params !== NULL) {
            $resp["params"] = $params;
        }

        $this->response->add(new JSonGeneratorImpl(JSonGenerator::COMMAND_TYPE, $resp));
    }

    /**
     * Afegeix una resposta de tipus HTML_TYPE al generador de respostes.
     *
     * @param string $id
     * @param string $ns
     * @param string $title
     * @param string $content
     * @param string $rev
     * @param string $type
     * @param $structured
     */
    public function addHtmlDoc($id, $ns, $title, $content, $rev, $type)
    {
        $contentData = array(
            'id' => $id,
            'ns' => $ns,
            'title' => $title,
            'content' => $content,
            'rev' => $rev,
            'type' => $type
        );

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::HTML_TYPE,
                $contentData)
        );
    }

    public function addPrintResponse($content)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::TO_PRINT,
                $content)
        );
    }

    public function addDiffDoc($id, $ns, $title, $content, $type, $rev1, $rev2 = NULL)
    {
        $contentData = array(
            'id' => $id,
            'ns' => $ns,
            'title' => $title,
            'content' => $content,
            'type' => $type,
            'rev1' => $rev1,
            'rev2' => $rev2
        );

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::DIFF_TYPE,
                $contentData)
        );
    }

    public function addNotificationResponse($action, $params)
    {
        $contentData = array(
            'action' => $action,
            'params' => $params
        );

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::NOTIFICATION,
                $contentData)
        );
    }

    public function addDraftDialog($id, $ns, $rev, $params, $timeout)
    {
        $contentData = [
            'id' => $id,
            'ns' => $ns,
            'rev' => $rev,
        ];

        $contentData['params'] = $params;
        $contentData['timeout'] = $timeout;

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::DRAFT_DIALOG,
                $contentData)
        );
    }

    /**
     * Afegeix una resposta de tipus MEDIA_TYPE al generador de respostes.
     *
     * @param string $id
     * @param string $ns
     * @param string $title
     * @param string $content
     */
    public function addMedia($id, $ns, $title, $content, $preserveMetaData)
    {
        $contentData = array(
            'id' => $id,
            'ns' => $ns,
            'title' => $title,
            'preserveMetaData' => $preserveMetaData,
            'content' => $content
        );

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::MEDIA_TYPE,
                $contentData)
        );
    }

    /**
     * Afegeix una resposta de tipus META_MEDIA_INFO al generador de respostes.
     *
     * @param string $docId
     * @param string[] $meta hash amb les metadades
     */
    public function addMetaMediaData($docId, $meta)
    {
        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::META_MEDIA_INFO,
                array(
                    "docId" => $docId,
                    "meta" => $meta,
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus MEDIADETAILS_TYPE al generador de respostes.
     *
     * @param string $id
     * @param string $ns
     * @param string $title
     * @param string $content
     */
    public function addMediaDetails($difftype, $mediado, $mediaDetailsAction, $id, $ns, $title, $content)
    {
        $contentData = array(
            'id' => $id,
            'ns' => $ns,
            'title' => $title,
            'content' => $content,
            'mediaDetailsAction' => $mediaDetailsAction,
            'mediado' => $mediado,
            'difftype' => $difftype
        );

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::MEDIADETAILS_TYPE,
                $contentData)
        );
    }

    /**
     * Afegeix una resposta de tipus META_MEDIADETAILS_INFO al generador de respostes.
     *
     * @param string $docId
     * @param string[] $meta hash amb les metadades
     */
    public function addMetaMediaDetailsData($docId, $meta)
    {
        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::META_MEDIADETAILS_INFO,
                array(
                    "docId" => $docId,
                    "meta" => $meta,
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus DATA_TYPE al generador de respostes.
     *
     * @param string $id
     * @param string $ns
     * @param string $title
     * @param string $content
     * @param string $draft
     * @param string[] $editing - Editing params
     */
    public function addWikiCodeDoc($id, $ns, $title, $content, $draft, $recover_drafts, $htmlForm, $editing, $timer, $rev = NULL, $autosaveTimer = NULL, $extra = NULL)
    {
        $contentData = [
            'id' => $id,
            'ns' => $ns,
            'title' => $title,
            'content' => $content,
            'htmlForm' => $htmlForm,
            'draft' => $draft,
            'editing' => $editing,
            "timer" => $timer,
            'rev' => $rev
        ];

        if (count($recover_drafts) > 0) {
            $contentData['recover_draft'] = $recover_drafts;
        }

        if ($autosaveTimer) {
            $contentData['autosaveTimer'] = $autosaveTimer;
        }

        if ($extra) {
            $contentData['extra'] = $extra;
        }

        // ALERTA[Xavi] Pendent de determinar com s'ha d'obtenir aquest valor (del projecte)
        $contentData['ignoreLastNSSections'] = 0;


        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::DATA_TYPE,
                $contentData)
        );
    }

    /**
     * Afegeix una resposta de tipus DATA_TYPE al generador de respostes.
     *
     * @param string $id
     * @param string $ns
     * @param string $title
     * @param string $content
     * @param string[] $draft
     * @param string[] $editing - Editing params
     */
    public function addRequiringDoc($id, $ns, $title, $action, $timer, $content, $type, $dialog = NULL)
    {
        $contentData = [
            'id' => $id,
            'ns' => $ns,
            'title' => $title,
            'action' => $action,
            'timer' => $timer,
            'content' => $content,
            'requiring_type' => $type,
        ];
        if ($dialog) {
            $contentData["dialog"] = $dialog;
        }

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::REQUIRING,
                $contentData)
        );
    }

    /**
     * Afegeix una resposta de tipus LOGIN_INFO al generador de respostes.
     *
     * @param string $loginRequest
     * @param string $loginResult
     */
    public function addLoginInfo($loginRequest, $loginResult, $userId = NULL)
    {
        $response = array(
            "loginRequest" => $loginRequest,
            "loginResult" => $loginResult
        );
        if ($userId) {
            $response["userId"] = $userId;
        }

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::LOGIN_INFO,
                $response)
        ); //afegir si és login(true) o logout(false)
    }


    /**
     * Afegeix una resposta de tipus SECTOK_DATA al generador de respostes.
     *
     * @param string $data dades del token de seguretat
     */
    public function addSectokData($data)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::SECTOK_DATA,
                $data)
        );
    }

    /**
     * Afegeix una resposta de tipus COMMAND_TYPE::CHANGE_WIDGET_PROPERTY
     *
     * @param string $widgetId
     * @param string $propertyName
     * @param mixed $propertyValue
     */
    public function addChangeWidgetProperty($widgetId, $propertyName, $propertyValue)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::COMMAND_TYPE,
                array(
                    "type" => JSonGenerator::CHANGE_WIDGET_PROPERTY,
                    "id" => $widgetId,
                    "propertyName" => $propertyName,
                    "propertyValue" => $propertyValue
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus COMMAND_TYPE::RELOAD_WIDGET_CONTENT al generador de respostes.
     *
     * @param string $widgetId
     */
    public function addReloadWidgetContent($widgetId)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::COMMAND_TYPE,
                array(
                    "type" => JSonGenerator::RELOAD_WIDGET_CONTENT,
                    "id" => $widgetId
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus COMMAND_TYPE::REMOVE_WIDGET_CHILD al generador de respostes.
     *
     * @param string $widgetId
     * @param string $childId
     */
    public function addRemoveWidgetChild($widgetId, $childId)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::COMMAND_TYPE,
                array(
                    "type" => JSonGenerator::REMOVE_WIDGET_CHILD,
                    "id" => $widgetId,
                    "childId" => $childId
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus COMMAND_TYPE::REMOVE_ALL_WIDGET_CHILDREN al generador de respostes.
     *
     * @param string $widgetId
     */
    public function addRemoveAllWidgetChildren($widgetId)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::COMMAND_TYPE,
                array(
                    "type" => JSonGenerator::REMOVE_ALL_WIDGET_CHILDREN,
                    "id" => $widgetId
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus REMOVE_CONTENT_TAB al generador de respostes.
     *
     * @param string $tabId
     */
    public function addRemoveContentTab($tabId)
    {
        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::REMOVE_CONTENT_TAB, $tabId)
        );
    }

    /**
     * Afegeix una resposta de tipus REMOVE_ALL_CONTENT_TAB al generador de respostes.
     */
    public function addRemoveAllContentTab()
    {
        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::REMOVE_ALL_CONTENT_TAB)
        );
    }

    /**
     * Afegeix una resposta de tipus REMOVE_ALL_CONTENT_TAB al generador de respostes.
     */
    public function addRemoveItemTree($treeId, $itemId)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::TREE,
                array(
                    "do" => "remove_item",
                    "treeId" => $treeId,
                    "itemId" => $itemId
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus REMOVE_ALL_CONTENT_TAB al generador de respostes.
     */
    public function addAddItemTree($treeId, $itemId)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::TREE,
                array(
                    "do" => "add_item",
                    "treeId" => $treeId,
                    "itemId" => $itemId
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus INFO_TYPE al generador de respostes.
     *
     * @param string $info
     */ //$type, $message, $id = null, $duration = -1)
    public function addInfoDta($info, $message = NULL, $id = NULL, $duration = -1, $timestamp = "")
    {
        if ($message) {
            $resp = array(
                "id" => $id,
                "type" => $info,
                "message" => $message,
                "duration" => $duration,
                "timestamp" => $timestamp
            );
        } else {
            $resp = $info;
        }
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::INFO_TYPE,
                $resp)
        );
    }

    /**
     * Afegeix una resposta de tipus CODE_TYPE_RESPONSE al generador de respostes.
     *
     * @param int $responseCode
     * @param string $info
     */
    public function addCodeTypeResponse($responseCode, $info = "")
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::CODE_TYPE_RESPONSE,
                array(
                    "code" => $responseCode,
                    "info" => $info,
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus SIMPLE_TYPE_RESPONSE al generador de respostes.
     *
     * @param $return
     */
    public function addSimpleTypeResponse($return)
    {
        $this->add(JSonGenerator::SIMPLE_TYPE_RESPONSE, $return);
    }

    /**
     * Afegeix una resposta de tipus ARRAY_TYPE_RESPONSE al generador de respostes.
     *
     * @param array $return
     */
    public function addArrayTypeResponse($return)
    {
        $this->add(JSonGenerator::ARRAY_TYPE_RESPONSE, $return);
    }

    /**
     * Afegeix una resposta de tipus ARRAY_TYPE_RESPONSE al generador de respostes.
     *
     * @param object $return
     */
    public function addObjectTypeResponse($return)
    {
        $this->add(JSonGenerator::OBJECT_TYPE_RESPONSE, $return);
    }

    /**
     * Afegeix una resposta de tipus META_INFO al generador de respostes.
     *
     * @param string $id
     * @param string[] $meta hash amb les metadades
     */
    public function addMetadata($id, $meta)
    {

        if (!$id || !$meta) {
            return;
        }

        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::META_INFO,
                array(
                    "id" => $id,
                    "meta" => $meta,
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus META_DIFF al generador de respostes.
     *
     * @param string $id
     * @param string[] $meta hash amb les metadades
     */
    public function addMetaDiff($id, $meta)
    {

        if (!$id || !$meta) {
            return;
        }

        $this->response->add(
            new JSonGeneratorImpl(JSonGenerator::META_DIFF,
                array(
                    "id" => $id,
                    "meta" => $meta,
                ))
        );
    }

    /**
     * Afegeix una resposta de tipus META_INFO al generador de respostes.
     *
     * @param string $id
     * @param string[] $meta hash amb les metadades
     */
    public function addExtraMetadata($id, $meta, $tit = NULL, $cont = NULL)
    {
        if ($tit) {
            $aMeta = array("id" => $meta, 'title' => $tit, 'content' => $cont, "docId" => $id);
        } else {
            $aMeta = $meta;
        }
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::EXTRA_META_INFO,
                array(
                    "id" => $id,
                    "meta" => $aMeta,
                )
            )
        );
    }

    /**
     * Retorna una cadena en format JSON amb totes les respostes codificades.
     *
     * @return string resposta codificada en JSON
     */
    public function getJsonResponse()
    {
        return ($this->response->getJson()) ? $this->response->getJsonEncoded() : NULL;
    }

    /**
     * Afegeix una resposta del tipus especificat amb les dades passades com argument al generador de respostes.
     *
     * @param int $type
     * @param mixed $data
     */
    private function add($type, $data)
    {
        $this->response->add(new JSonGeneratorImpl($type, $data));
    }

    /**
     * Afegeix una resposta de tipus PLAIN al generador de respostes.
     * La resposta el un text pla sense format igual que l'original
     */
    public function setEncodedResponse($data)
    {
        $this->response = new JSonJustEncoded($data);
    }

    /**
     * Afegeix una resposta de tipus ADMIN_TAB al generador de respostes.
     *
     * @param string $containerId identificador del contenidor on afegir la pestanya
     * @param string $tabId identificador de la pestanya
     * @param string $title títol de la pestanya
     * @param string $content contingut html amb la llista de tasques
     * @param string $urlBase urlBase de la comanda on dirigir les peticions de cada tasca
     */
    public function addAdminTab($containerId, $tabId, $title, $content, $urlBase)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::ADMIN_TAB,
                array(
                    "type" => JSonGenerator::ADD_ADMIN_TAB,
                    "containerId" => $containerId,
                    "tabId" => $tabId,
                    "title" => $title,
                    "content" => $content,
                    "urlBase" => $urlBase
                )
            )
        );
    }

    /* @deprecated */
    public function addShortcutsTab($containerId, $tabId, $title, $content, $urlBase)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::SHORTCUTS_TAB,
                array(
                    "type" => JSonGenerator::ADD_SHORTCUTS_TAB,
                    "containerId" => $containerId,
                    "tabId" => $tabId,
                    "title" => $title,
                    "content" => $content,
                    "urlBase" => $urlBase
                )
            )
        );
    }

    /* @deprecated */
    public function addRemoveShortcutsTab($containerId, $tabId)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::SHORTCUTS_TAB,
                array(
                    "type" => JSonGenerator::REMOVE_SHORTCUTS_TAB,
                    "containerId" => $containerId,
                    "tabId" => $tabId
                )
            )
        );
    }

    public function addAddTab($containerId, $contentParams, $position = NULL, $selected = FALSE, $containerClass = NULL)
    {
        $responseParams = array(
            "type" => JSonGenerator::ADD_TAB,
            "containerId" => $containerId,
            "contentParams" => $contentParams,
            "selected" => $selected,
        );
        if ($position) {
            $responseParams["position"] = $position;
        }
        if ($containerClass) {
            $responseParams["containerClass"] = $containerClass;
        }
        $this->response->add(new JSonGeneratorImpl(JSonGenerator::TAB, $responseParams));
    }

    public function addRemoveTab($containerId, $tabId)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::TAB,
                array(
                    "type" => JSonGenerator::REMOVE_TAB,
                    "containerId" => $containerId,
                    "tabId" => $tabId
                )
            )
        );
    }


    public function addRemoveAdminTab($containerId, $tabId)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::ADMIN_TAB,
                array(
                    "type" => JSonGenerator::REMOVE_ADMIN_TAB,
                    "containerId" => $containerId,
                    "tabId" => $tabId
                )
            )
        );
    }

    /**
     * Afegeix una resposta de tipus ADMIN_TASK al generador de respostes.
     *
     * @param string $id
     * @param string $ns
     * @param string $title
     * @param string $content
     */
    public function addAdminTask($id, $ns, $title, $content)
    {

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::ADMIN_TASK,
                array(
                    'id' => $id,
                    'ns' => $ns,
                    'title' => $title,
                    'content' => $content
                )
            )
        );
    }

    public function addUserProfile($id, $ns, $title, $content) {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::USER_PROFILE,
                array(
                    'id' => $id,
                    'ns' => $ns,
                    'title' => $title,
                    'content' => $content
                )
            )
        );
    }

    /**
     * Afegeix una resposta de tipus REVISIONS al generador de respostes.
     *
     * @param $id
     * @param $revisions
     *
     */
    public function addRevisionsTypeResponse($id, $revisions)
    {
        $this->add(
            JSonGenerator::REVISIONS_TYPE
            , array(
                'id' => $id,
                'revisions' => $revisions,
                'type' => 'revisions'
            )
        );
    }

    /**
     * Afegeix una resposta de tipus EXTRA_CONTENT_STATE al generador de
     * respostes. Aquest tipus de resposta permet als plugins afegir valors
     * extres a l'estat d'un contingut identificat per un id.
     *
     * @param $id
     * @param $type
     * @param $value
     *
     */
    public function addExtraContentStateResponse($id, $type, $value)
    {
        $this->add(
            JSonGenerator::EXTRA_CONTENT_STATE
            , array(
                'id' => $id,
                'type' => $type,
                'value' => $value
            )
        );
    }

    public function addWikiCodeDocPartial($structure, $timer = NULL, $hasDraft = NULL, $autosaveTimer = NULL, $extra = NULL)
    {
        $contentData = $structure;

        if ($timer) {
            $contentData['timer'] = $timer;
        }

        if ($hasDraft) {
            $contentData['hasDraft'] = $hasDraft;
        }

        if ($autosaveTimer) {
            $contentData['autosaveTimer'] = $autosaveTimer;
        }

        if ($extra) {
            $contentData['extra'] = $extra;
        }

        // ALERTA[Xavi] Pendent de determinar com s'ha d'obtenir aquest valor (del projecte)
        $contentData['ignoreLastNSSections'] = 0;


        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::HTML_PARTIAL_TYPE,
                $contentData)
        );
    }

    /**
     * Afegeix una resposta de tipus INFO_TYPE al generador de respostes.
     *
     * @param $id - id del document
     * @param $timeout
     */
    public function addRefreshLock($id, $ns, $timeout)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::LOCK_DATA,
                [
                    'id' => $id,
                    'ns' => $ns,
                    'timeout' => $timeout,
                ])
        );
    }

    public function addNotification($action, $params = [])
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::NOTIFICATION,
                [
                    'action' => $action,
                    'params' => $params
                ])
        );
    }

    /**
     * Afegeix una resposta de tipus CONTROLMANAGER
     *
     * @param string $do
     * @param array $action
     * @param string $updateViewHandler
     */
    public function addControlManager($do, $action, $updateViewHandler = NULL)
    {
        $contentData = array(
            "do" => $do,
            "actions" => $action,
            "updateViewHandler" => $updateViewHandler
        );
        $this->add(JSonGenerator::CONTROLMANAGER, $contentData);
    }

    public function addDialog($title, $text, $buttons = [])
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::CUSTOM_DIALOG,
                [
                    'title' => $title,
                    'text' => $text,
                    'buttons' => $buttons
                ])
        );
    }


    /**
     * Afegeix una resposta de tipus FORM_TYPE al generador de respostes.
     * @param $id
     * @param $ns
     * @param $form
     */
    public function addForm($id, $ns, $title, $form, $values, $extra = [])
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::FORM_TYPE,
                [
                    'id' => $id,
                    'ns' => $ns,
                    'title' => $title,
                    'content' => $form,
                    'originalContent' => $values,
                    'extra' => $extra
                ]
            )
        );
    }

    /**
     * Afegeix una resposta de tipus PROJECT_EDIT al generador de respostes.
     */
    public function addEditProject($id, $ns, $title, $form, $values, $hasDraft=NULL, $autosaveTimer=NULL, $originalLastmod=NULL, $extra=[]) {
        $contentData['id'] = $id;
        $contentData['ns'] = $ns;
        $contentData['title'] = $title;
        $contentData['content'] = $form;
        $contentData['originalContent'] = $values;
        if ($autosaveTimer)
            $contentData['autosaveTimer'] = $autosaveTimer;
        if ($hasDraft)
            $contentData['hasDraft'] = $hasDraft;
        if ($originalLastmod)
            $contentData['originalLastmod'] = $originalLastmod;
        $contentData['extra'] = $extra;

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::PROJECT_EDIT_TYPE,
                $contentData
            )
        );
    }

    /**
     * Afegeix una resposta de tipus PROJECT_VIEW al generador de respostes.
     */
    public function addViewProject($id, $ns, $title, $form, $values, $hasDraft=NULL, $extra=[]) {
        $contentData['id'] = $id;
        $contentData['ns'] = $ns;
        $contentData['title'] = $title;
        $contentData['content'] = $form;
        $contentData['originalContent'] = $values;
        if ($hasDraft)
            $contentData['hasDraft'] = $hasDraft;
        $contentData['extra'] = $extra;

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::PROJECT_VIEW_TYPE,
                $contentData
            )
        );
    }

    /**
     * Afegeix una resposta de tipus HTML_TYPE al generador de respostes.
     *
     * @param string $id
     * @param string $ns
     * @param string $title
     * @param string $content
     * @param array $aFormArgs
     * @param array $aLinkArgs
     */
    public function addRecents($id, $title, $content, $aFormArgs, $linkArgs)
    {
        $contentData = array(
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'aRequestFormArgs' => $aFormArgs,
            'requestLinkArgs' => $linkArgs,

        );

        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::RECENTS,
                $contentData)
        );
    }

    public function addContenttoolTimerStop($id)
    {
        $contentData = array(
            'id' => $id,
            'action' => "stop",
        );
        $this->response->add(
            new JSonGeneratorImpl(
                JsonGenerator::CT_TIMER,
                $contentData
            )
        );
    }


    public function addUserState($state = [])
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::USER_STATE,
                $state)
        );
    }

    public function addUpdateLocalDrafts($ns, $drafts)
    {
        $this->response->add(
            new JSonGeneratorImpl(
                JSonGenerator::UPDATE_LOCAL_DRAFTS,
                [
                    'ns' => $ns,
                    'drafts' => $drafts
                ]
            )
        );
    }

    /**
     * Genera un element amb la informació correctament formatada i afegeix el timestamp. Si no s'especifica el id
     * s'assignarà el id del document que s'estigui gestionant actualment.
     *
     * Per generar un info associat al esdeveniment global s'ha de passar el id com a buit, es a dir
     *
     * @param string $type - tipus de missatge [notify, info, success, warning, error, debug]
     * @param string|string[] $message - Missatge o missatges associats amb aquesta informació
     * @param string $id - id del document al que pertany el missatge
     * @param int $duration - Si existeix indica la quantitat de segons que es mostrarà el missatge
     *
     * @return array - array amb la configuració del item de informació
     */
    public static function generateInfo($type, $message, $id = '', $duration = -1)
    {
        return [
            "id" => str_replace(':', '_', $id),  //netejar l'ID i posar : a _
            "type" => $type,
            "message" => $message,
            "duration" => $duration,
            "timestamp" => date("d-m-Y H:i:s")
        ];
    }

    // En els casos en que hi hagi discrepancies i no hi haci cap preferencia es fa servir el valor de A
    public static function addInfoToInfo($infoA, $infoB)
    {
        // Els tipus global de la info serà el de major gravetat: "debug" > "error" > "warning" > "info"
        $info = [];

        if ($infoA['type'] == 'debug' || $infoB['type'] == 'debug') {
            $info['type'] = 'debug';
        } else if ($infoA['type'] == 'error' || $infoB['type'] == 'error') {
            $info['type'] = 'error';
        } else if ($infoA['type'] == 'warning' || $infoB['type'] == 'warning') {
            $info['type'] = 'warning';
        } else {
            $info['type'] = $infoA['type'];
        }

        // Si algun dels dos te duració ilimitada, aquesta perdura
        if ($infoA['duration'] == -1 || $infoB['duration'] == -1) {
            $info['duration'] = -1;
        } else {
            $info['duration'] = $infoA['duration'];
        }

        // El $id i el timestamp ha de ser el mateix per a tots dos
        $info ['timestamp'] = $infoA['timestamp'];
        $info ['id'] = $infoA['id'];

        $messageStack = [];

        if (is_string($infoA ['message'])) {

            $messageStack[] = $infoA['message'];

        } else if (is_array($infoA['message'])) {

            $messageStack = $infoA['message'];

        }

        if (is_string($infoB ['message'])) {

            $messageStack[] = $infoB['message'];

        } else if (is_array($infoB['message'])) {

            $messageStack = array_merge($messageStack, $infoB['message']);

        }

        $info['message'] = $messageStack;

        return $info;
    }


}
