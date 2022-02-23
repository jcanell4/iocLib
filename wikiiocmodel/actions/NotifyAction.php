<?php
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once DOKU_INC . 'inc/inc_ioc/MailerIOC.class.php';

class NotifyAction extends AbstractWikiAction {
    const DO_INIT    = "init";
    const DO_ADD     = "add";
    const DO_ADDMESS = "add_message";
    const DO_GET     = "get";
    const DO_CLOSE   = "close";
    const DO_UPDATE  = "update";
    const DO_DELETE  = "delete";

    const DEFAULT_MESSAGE_TYPE = 'info';

    /*
     * NO CAL. Ho deixo per il·lustrar com mentenir constants amb un únic orígen de dades, sense necessitat de conèixer la seva classe.
    static $TYPE_ALERT;
    static $TYPE_MESSAGE;
    static $TYPE_DIALOG;
    static $TYPE_REQUIREMENT;
    static $TYPE_RELEASE;
    static $TYPE_CANCEL_NOTIFICATION;
    static $TYPE_EXPIRING;
     */

    protected $dokuNotifyModel;
    protected $isAdmin;

    public function __construct($isAdmin=FALSE) {
        $this->isAdmin = $isAdmin;

        /*
        $notifyClass = $persistenceEngine->getNotifyDataQueryClass();
        self::$TYPE_ALERT = $notifyClass::TYPE_ALERT;
        self::$TYPE_MESSAGE = $notifyClass::TYPE_MESSAGE;
        self::$TYPE_DIALOG = $notifyClass::TYPE_DIALOG;
        self::$TYPE_REQUIREMENT = $notifyClass::TYPE_REQUIREMENT;
        self::$TYPE_RELEASE = $notifyClass::TYPE_RELEASE;
        self::$TYPE_CANCEL_NOTIFICATION = $notifyClass::TYPE_CANCEL_NOTIFICATION;
        self::$TYPE_EXPIRING = $notifyClass::TYPE_EXPIRING;
         */
    }

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $type = WikiGlobalConfig::getConf('notifier_type', 'wikiiocmodel');
        $this->dokuNotifyModel = $modelManager->getNotifyModel($type);
        return $this->initParams();
    }

    private function initParams() {
        $response['params'] = $this->dokuNotifyModel->init();
        $response['action'] = 'init_notifier';
        return $response;
    }

    //Obligatori interficies DokuAction
    protected function startProcess() {}
    protected function runProcess() {}

    // Aquí es genera la resposta
    protected function responseProcess() {
        $option = $this->params[PageKeys::KEY_DO];
        $response['notifications'] = [];

        switch ($option) {
            //TODO[Xavi] per fer proves només afegim una info amb el resultat, això ha de fer servir el propi notifier
            case self::DO_INIT: // Retorna la resposta que inicia el sistema de notificacions segons calgui: o el processNotifications o el processWebSocketClient
                $notificationInit = $this->initParams();
                $response['notifications'][] = $notificationInit;

                if ($notificationInit['params']['type'] == "ajax") {
                    $response['notifications'][] = $this->popNotifications();
                }
                break;

            case self::DO_ADDMESS:
                $notifyResponse = $this->notifyMessageToFrom();
                $response['notifications'][] = $notifyResponse['notifications'];
                $response['info'] = $notifyResponse['info'];
                break;

            case self::DO_GET: // Obtenir totes les notificacions pel idUser, cridat periodicament pel timer, popNotifications()
                $response['notifications'][]= $this->popNotifications();
                break;

            case self::DO_CLOSE: // Elimina totes les notificacions pendents pel usuari loginat, cridat al fer logout, close()
                $response['notifications'][] = $this->close();
                break;

            case self ::DO_UPDATE:
                $response['notifications'][] = $this->update();
                break;

            case self ::DO_DELETE:
                $response['notifications'][] = $this->delete();
                break;

            default:
                // TODO[Xavi] Canviar la excepció per una propia, per determinar el codi
                throw new UnavailableMethodExecutionException("NotifyAction#responseProcess");
        }

        return $response;
    }

    protected function generateAboutDocument($id) {
        return sprintf(WikiIocLangManager::getLang("doc_message"), $id);
    }

    public function notifyMessageToFrom()
    {
        $senderId = $this->getCurrentUser();
        $senderUser = WikiIocInfoManager::getInfo("userinfo");
        $receivers = $this->getReceivers($this->params['to']);
        $notification = null;

        foreach ($receivers as $receiver) {
            $notification = $this->buildMessage($this->params['message'], $senderId, $docId = $this->params['id'], $receiver['id'], $this->params['type'], $this->params['rev'], $this->params["data-call"]);

            if ($this->params['send_email']) {
                $this->sendNotificationByEmail($senderUser, $receiver, $notification['title'], $notification['content']['textMail']);
            }
            $this->dokuNotifyModel->notifyMessageToFrom($notification['content'], $receiver['id'], $senderId, NotifyDataQuery::MAILBOX_RECEIVED);

        }

        $receiversList = $this->getReceiversIdAsString($receivers);
        $message = $this->buildMessage($this->params['message'], $senderId, $this->params['id'], $receiversList, null, $this->params['rev'], $this->params["data-call"]);
        $notification = $this->dokuNotifyModel->notifyMessageToFrom($message ['content'], $senderId, null, NotifyDataQuery::MAILBOX_SEND, true);

        $response['info'] = self::generateInfo('success', sprintf(WikiIocLangManager::getLang("notifation_send_success"), $receiversList));
        $response['notifications']['params']['notification'] = $notification;
        $response['notifications']['action'] = 'notification_sent';

        return $response;
    }

    private function getReceiversIdAsString($receivers) {
        $filteredReceivers = [];

        for ($i=0; $i<count($receivers); $i++) {
            $filteredReceivers[] = $receivers[$i]['id'];
        }

        return implode(', ', $filteredReceivers);
    }


    private function buildMessage($data, $senderId, $docId, $receivers, $type=self::DEFAULT_MESSAGE_TYPE, $rev=null, $dataCall=null) {
        if (is_string($data)) {
            $title = sprintf(WikiIocLangManager::getLang("title_message_notification_with_id"), $senderId, $docId);

            if ($dataCall) {
                $dataCall = "data-call=\"$dataCall\"";
            }else{
                $dataCall = "";
            }           
            $mainMessage = p_render('xhtml', p_get_instructions($data), $info);
            if ($rev) {
                 $url =  wl($docId, ['rev' => $rev], true);
                $message = "<p>".sprintf(WikiIocLangManager::getLang("doc_message_with_rev"), $url, $url, $dataCall, $docId , $rev) . "</p>" . $mainMessage;
            } else {
                $url = wl($docId, '', true);
                $message = "<p>".sprintf(WikiIocLangManager::getLang("doc_message"), $url, $url, $dataCall, $docId) . "</p>" . $mainMessage;
            }

            $textMail = "<p>".sprintf(WikiIocLangManager::getLang("mail_message"), DOKU_URL, DOKU_URL, $docId) .  "</p>" . $mainMessage;

            if ($receivers) {
                $message = "<p>".sprintf(WikiIocLangManager::getLang("message_notification_receivers"), $receivers) . "</p>" . $message;
                $textMail = "<p>".sprintf(WikiIocLangManager::getLang("message_notification_receivers"), $receivers) . "</p>". $textMail;
            }

            $content = [
                'type' => $type,
                'id' => $docId . '_' . $senderId,
                'title' => $title,
                'text' => $message,
                'textMail' => $textMail
            ];
        } else {
            $title = $data['title'];
            $content = $data;
        }

        return ['title' => $title, 'content'=>$content];
    }



    private function getReceivers($receiversString) {
        global $auth;

        $receiversArray = preg_split('/[\s;,|.]+/', $receiversString);
        $receiversUsers = [];

        foreach ($receiversArray as $receiver) {
            if (strlen($receiver) == 0) {
                continue;
            }

            $receiverUser= $auth->getUserData($receiver);

            // TODO[Xavi] Si no existeix l'usuari llençar excepció

            if (!$receiverUser) {
                throw new UnknownUserException($receiver);
            } else {
                $receiverUser['id']= $receiver;
                $receiversUsers[] = $receiverUser;
            }
        }

        return $receiversUsers;
    }

    private function sendNotificationByEmail($senderUser, $receiverUser, $subject, $message) {
        $subject = sprintf(WikiIocLangManager::getLang("notificaction_email_subject"), $subject);

        $mail = new MailerIOC();
        $mail->to($receiverUser['id'] . ' <' . $receiverUser['mail'] . '>');
        $mail->subject($subject);
        $mail->setBody(preg_replace("/\n/", "", $message));
        $mail->from($senderUser['mail']);
        $ret = $mail->send();
        return $ret;
    }

    public function notifyTo()
    {
        // ALERTA[Xavi] No s'utilitza
    }


    public function update() {
        if ($this->params['blackboardId'] && $this->isAdmin) {
            $blackboardId = $this->params['blackboardId'];
        } else {
            $blackboardId = $this->getCurrentUser();
        }

        $response['params'] = [];

        $this->dokuNotifyModel->update($this->params['notificationId'], $blackboardId, $this->params['changes']);

        $response['action'] = 'notification_updated';
        $response['params']['notifications'] = $this->dokuNotifyModel->popNotifications($blackboardId);

        return $response;

    }

    public function delete() {
        if ($this->params['blackboardId'] && $this->isAdmin) {
            $blackboardId = $this->params['blackboardId'];
        } else {
            $blackboardId = $this->getCurrentUser();
        }

        $response['params'] = [];

        $this->dokuNotifyModel->delete($this->params['notificationId'], $blackboardId);

        $response['action'] = 'notification_deleted';
        $response['params']['notifications'] = $this->dokuNotifyModel->popNotifications($blackboardId);

        return $response;
    }



    public function popNotifications()
    {
        $userId = $this->getCurrentUser();
        $response['params'] = [];
        $response['params']['notifications'] = $this->dokuNotifyModel->popNotifications($userId, $this->params['since']);
        $response['action'] = 'notification_received';

        return $response;
    }

    public function close()
    {
        $userId = $this->getCurrentUser();
        $response['params'] = $this->dokuNotifyModel->close($userId);
        $response['action'] = 'close_notifier';

        return $response;
    }

    private function getCurrentUser()
    {
        return $_SERVER['REMOTE_USER'];
    }
}
