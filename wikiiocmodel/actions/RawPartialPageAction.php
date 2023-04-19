<?php
/**
 * Description of RawPartialPageAction
 * @author josep
 */
if (!defined('DOKU_INC')) die();

class RawPartialPageAction extends EditPageAction {
    private $lockStruct;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_EDIT;
    }

    protected function startProcess() {
        parent::startProcess();

        // TODO[Xavi] Actualitzar al client les crides dels esborranys per fer servir el KEY_DO
        if ($this->params[PageKeys::KEY_DO] === PageKeys::KEY_TO_REQUIRE) {
            $this->params[PageKeys::KEY_TO_REQUIRE] = TRUE;
        } else if ($this->params[PageKeys::KEY_DO] === PageKeys::KEY_RECOVER_LOCAL_DRAFT) {
            $this->params[PageKeys::KEY_RECOVER_LOCAL_DRAFT] = TRUE;
        } else if ($this->params[PageKeys::KEY_DO] === PageKeys::KEY_RECOVER_LOCAL_DRAFT) {
            $this->params[PageKeys::KEY_RECOVER_LOCAL_DRAFT] = TRUE;
        }

        $this->dokuPageModel->init($this->params[PageKeys::KEY_ID],
            $this->params[PageKeys::KEY_EDITING_CHUNKS],
            $this->params[PageKeys::KEY_SECTION_ID],
            $this->params[PageKeys::KEY_REV],
            $this->params[PageKeys::KEY_RECOVER_DRAFT]);

        // Abans de generar la resposta s'ha d'eliminar l'esborrany complet si escau
        if ($this->params[PageKeys::KEY_DISCARD_DRAFT]) {
            $this->getModel()->removeFullDraft($this->params[PageKeys::KEY_ID]);
        }
    }

    // ALERTA[Xavi] Identic al RawPageAction#runProcess();
    protected function runProcess() {
        global $ACT;

        if (!WikiIocInfoManager::getInfo(WikiIocInfoManager::KEY_EXISTS)) {
            throw new PageNotFoundException($this->params[PageKeys::KEY_ID]);
        }

        $ACT = IocCommon::act_permcheck($this->defaultDo);

        if ($ACT == PageKeys::DW_ACT_DENIED) {
            throw new InsufficientPermissionToEditPageException($this->params[PageKeys::KEY_ID]);
        }

        if (!$this->params[PageKeys::KEY_REV]) {
            $this->lockStruct = $this->requireResource(TRUE);
        }
    }

    protected function responseProcess()
    {
        $response = [];
        $data = $this->getModel()->getData(TRUE);

        // 0) Si ja hi ha un chunk en edició, s'han d'ignorar els drafts FULL: comprovem si s'ha passat la data de de l'structured
        $ignoreDrafts = count($this->params[PageKeys::KEY_EDITING_CHUNKS])>1
            && !isset($this->params[PageKeys::STRUCTURED_LAST_LOCAL_DRAFT_TIME])
                && !isset($this->params[PageKeys::KEY_RECOVER_LOCAL_DRAFT]);

        // 1) Ja s'ha recuperat el draft local
        if ($this->params[PageKeys::KEY_RECOVER_LOCAL_DRAFT] && !$ignoreDrafts) {

            $response = $this->_getLocalDraftResponse($data);

        } else if($this->lockState()==self::LOCKED_BEFORE){
            //-1 L'usuari te obert el document en una altra sessio

            // ALERTA[Xavi] Copiat de "bloquejat" el missatge enviat es l'únic que canvia.
            //  No es pot editar. Cal esperar que s'acabi el bloqueig
            $response = $this->_getSelfLockedDialog($data); // <-- acció equivalent al RawPageAction
//            $response['meta'] = $this->addMetaTocResponse();
            $this->addMetaTocResponse($response);
            $response[PageKeys::KEY_REVISIONS] = $this->getRevisionList();

        } else {

            // 2.1) Es demana recuperar el draft?
            if ($this->params[PageKeys::KEY_RECOVER_DRAFT] === TRUE && !$ignoreDrafts) {

                $response = $this->_getDraftResponse($data); // ALERTA[Xavi] Els drafts sempre es recuperaran localment, això ja no s'haurà de cridar mai

                // 2.2) Es troba desbloquejat?
            } else if (!$data['structure']['locked']) { //

                if ($this->params[PageKeys::KEY_RECOVER_DRAFT] === FALSE || $ignoreDrafts) {

                    // 2.2.1) S'ha especificat recuperar el document
                    $response = $this->_getDocumentResponse($data);
                } else {




                    // 2.2.1) Es generarà el dialog de draft pertinent, o el document si no hi ha cap draft per enviar
                   $response = $this->_getDialogOrDocumentResponse($data);

                   if($this->params[PageKeys::KEY_TO_REQUIRE]){
                        // TODO: afegir el 'meta' que correspongui perquè si ve del requiring dialog, el content tool es crerà de nou
//                       $response['meta'][] = $this->addMetaTocResponse();
                       $this->addMetaTocResponse($response);
                        // TODO: afegir les revisions
                       $response[PageKeys::KEY_REVISIONS] = $this->getRevisionList();
                   }
                }

                // 2.3) El document es troba bloquejat
            } else {

                // TODO[Xavi]El document està bloquejat
                //  No es pot editar. Cal esperar que s'acabi el bloqueig
                 $response = $this->_getWaitingUnlockDialog($data); // <-- acció equivalent al RawPageAction
                // TODO: afegir el 'meta' que correspongui perquè si va al requiring dialog, el content tool es crerà de nou
//                $response['meta'][] = $this->addMetaTocResponse();
                $this->addMetaTocResponse($response);
                // TODO: afegir les revisions
                $response[PageKeys::KEY_REVISIONS] = $this->getRevisionList();

            }
        }

        $response["lockInfo"] = $this->lockStruct["info"];

        $ns = isset($response['ns']) ? $response['ns'] : $response['structure']['ns'];
        //$response['meta'][] = $this->addNotificationsMetaToResponse($response, $ns);
        $this->addNotificationsMetaToResponse($response, $ns);

        return $response;
    }


    private function generateOriginalCall()
    {
        // ALERTA[Xavi] Cal afegir el  ns, ja que aquest no forma part dels params

        $originalCall['ns'] = $this->params[PageKeys::KEY_ID];
        $originalCall['id'] = WikiPageSystemManager::getContainerIdFromPageId($this->params[PageKeys::KEY_ID]);
        $originalCall['rev'] = $this->params[PageKeys::KEY_REV];
        $originalCall['section_id'] = $this->params[PageKeys::KEY_SECTION_ID];
        $originalCall['editing_chunks'] = $this->params[PageKeys::KEY_EDITING_CHUNKS];

        return $originalCall;
    }

    private function lockState()    {
        return $this->lockStruct["state"];
    }

    private function _getLocalDraftResponse($data)
    {
        $response = $data;
        $response[PageKeys::KEY_RECOVER_LOCAL_DRAFT] = true;
        $response['info'] = self::generateInfo('warning', WikiIocLangManager::getLang('local_draft_editing'));

        return $response;
    }

    private function _getDraftResponse($data)
    {
        // Existeix el KEY_RECOVER_DRAFT i es cert
        // Acció: recuperar esborrany

        $response = $data;

        $this->getModel()->replaceContentForChunk($response['structure'], $this->params[PageKeys::KEY_SECTION_ID],
            $response["draft"]['content']);
        $response['info'] = self::generateInfo('warning', WikiIocLangManager::getLang('draft_editing'));

        return $response;
    }

    private function _getConflictDialogResponse($response)
    {
        $response['original_call'] = $this->generateOriginalCall();
        $response['id'] = WikiPageSystemManager::getContainerIdFromPageId($this->params[PageKeys::KEY_ID]);
        $response['show_draft_conflict_dialog'] = true;
        $response['info'] = self::generateInfo('warning', WikiIocLangManager::getLang('draft_found'));

        return $response;
    }

    private function _getDraftInfo($data) {

        $draftInfo ['draftType'] = $data['draftType'];
        $draftInfo['local'] = false;

        // ALERTA[Xavi] QUE FEM: Calcular la data dels esborranys locals
        $fullLastLocalDraftTime = $this->params[PageKeys::FULL_LAST_LOCAL_DRAFT_TIME];
        $structuredLastLocalDraftTime = $this->params[PageKeys::STRUCTURED_LAST_LOCAL_DRAFT_TIME];

        // Si l'esborrany estructurad local es més recent que l'esborrany complet local, ignorem l'esborrany local complet
        // ALERTA[Xavi] QUE FEM: Descartar la data del esborrany complet local si el parcial es més recent
        if ($structuredLastLocalDraftTime >= $fullLastLocalDraftTime) {
            $fullLastLocalDraftTime = null;
        }

        // ALERTA[Xavi] QUE FEM: No existeix el KEY_RECOVER_DRAFT, ni KEY_DISCARD_DRAFT, però existeix un FULL LOCAL DRAFT, comprovem si es més recent el FULL REMOT
        if (!isset($this->params[PageKeys::KEY_RECOVER_DRAFT]) && !$this->params[PageKeys::KEY_DISCARD_DRAFT]) {
            if ($fullLastLocalDraftTime) {
                // obtenir la data del draft full local
                $fullLastSavedDraftTime = $this->dokuPageModel->getFullDraftDate();
                if ($fullLastLocalDraftTime > $fullLastSavedDraftTime) { // local es més recent
                    $draftInfo ['local'] = true;
                    $draftInfo ['draftType'] = PageKeys::LOCAL_FULL_DRAFT;
                }

                // ALERTA[Xavi] QUE FEM: Igual que l'anterior però amb STRUCTURED LOCAL
            } else if ($structuredLastLocalDraftTime) {
                $structuredLastSavedDraftTime = $this->dokuPageModel->getStructuredDraftDate();

                if ($structuredLastLocalDraftTime > $structuredLastSavedDraftTime) { // local es més recent
                    $draftInfo ['local'] = true;
                    $draftInfo ['draftType'] = PageKeys::LOCAL_PARTIAL_DRAFT;
                }
            }

        }

        return $draftInfo;
    }

    private function _getDraftDialogResponse($data)
    {
        $response = $this->generateOriginalCall();
        $response['show_draft_dialog'] = true;
        $response['title'] = $data['structure']['title'];
        $response['info'] = self::generateInfo('warning', WikiIocLangManager::getLang('partial_draft_found'));
        $response['lastmod'] = $data['structure']['date'];
        $response['content'] = $data['content']['editing'];
        $response['draft'] = $data['draft'];

        return $response;
    }

    private function _getDocumentResponse($data) {
        $message = $this->generateLockInfo($this->lockState(), $this->params[PageKeys::KEY_ID], TRUE, $this->params[PageKeys::KEY_SECTION_ID]);
        $data['info'] = self::addInfoToInfo($data['info'], $message);
        return $data;
    }

    private function _getDialogOrDocumentResponse($data)
    {
        $draftInfo = $this->_getDraftInfo($data);

        switch ($draftInfo['draftType']) {
            // Conflicte de drafts
            case PageKeys::LOCAL_FULL_DRAFT:
            case PageKeys::FULL_DRAFT:
                // Conflict
                $response = $this->_getConflictDialogResponse($data);
                break;

            // Existeix un draft parcial
            case PageKeys::LOCAL_PARTIAL_DRAFT:
            case PageKeys::PARTIAL_DRAFT:
                $response = $this->_getDraftDialogResponse($data);
                $response['local'] = $draftInfo['local'];
                break;

            // No hi ha draft, es mostrarà el document
            case PageKeys::NO_DRAFT:
                $response = $this->_getDocumentResponse($data);
                break;

            default:
                throw new UnknownTypeParamException($draftInfo['draftType']);
        }

        if ($draftInfo['draftType'] === PageKeys::FULL_DRAFT || $draftInfo['draftType'] === PageKeys::PARTIAL_DRAFT) {
            // TODO: Afegir a la resposta els esborranys remots per actualitzar els locals (
            $response['update_drafts'][$draftInfo['draftType']] = $data['draft'];
        }

        return $response;
    }

    private function _getWaitingUnlockDialog($data)
    {
        $resp = $this->_getDocumentResponse($data);
        //TODO [Josep][Xavi] Cal implementar quan estigui fet el sistema de diàlegs al client.
        //Aquí caldrà avisar que no és possible editar l'esborrany perquè hi ha algú editant prèviament el document
        // i es podrien perdre dades. També caldrà demanar si vol que l'avisin quan acabi el bloqueig
        return $resp;
    }

    private function _getSelfLockedDialog($data)
    {
        $resp = $this->_getDialogOrDocumentResponse($data);
        $resp["structure"]["locked_before"]=true;
//        $resp['structure']['locked'] = true;

        //TODO [Josep] Cal implementar quan estigui fet el sistema de diàlegs al client.
        //Aquí caldrà avisar que no és possible editar l'esborrany perquè hi ha algú editant prèviament el document
        // i es podrien perdre dades. També caldrà demanar si vol que l'avisin quan acabi el bloqueig
        return $resp;
    }


    /**
     * Això no es fa servir, es va descartar perquè la edició de documents parcials en l'editor
     * WYSIWYG donava molts problemes i la execució de pandoc no acabava de funcionar bé.
     * @deprecated
     */
    protected function translateToDW($text){


        $trans = new MarkDown2DikuWikiTranslator();
        $text = Html2DWParser::parse($text);

//        exec(DOKU_INC."../pandoc/convHtml2MdwFromText.sh \"$text\"", $return, $exit);
//        $text = implode ( "\n" , $return );
        return $trans->getRenderedContent($trans->getInstructions($text));
    }

    /**
     * Això no es fa servir, es va descartar perquè la edició de documents parcials en l'editor
     * WYSIWYG donava molts problemes i la execució de pandoc no acabava de funcionar bé.
     * @deprecated
     */
    protected function translateToHTML($text){
        $trans = new DikuWiki2MarkDownTranslator();
        $mdFormat=$trans->getRenderedContent($trans->getInstructions($text));
        $retExec = exec(DOKU_INC."../pandoc/convMdw2HtmlFromText.sh \"$mdFormat\"", $return, $exit);

        return implode ( "\n" , $return );
    }
}


