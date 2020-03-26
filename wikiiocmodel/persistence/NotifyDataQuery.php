<?php
/**
 * Description of NotifyDataQuery
 * @author Xavier García <xaviergaro.dev@gmail.com>
 */
if (!defined('DOKU_INC')) die();

class NotifyDataQuery extends DataQuery
{
    // TODO[Xavi] moure a altre fitxer com els PageKeys?
    const NOTIFICATION_ID = 'notification_id';
    const SENDER_ID = 'sender_id';
    const DATA = 'data';
    const TYPE = 'type'; // ALERT, MESSAGE, DIALOG
    const READ = 'read';
    const MAILBOX = 'mailbox';
    const TIMESTAMP = 'timestamp';

    const TYPE_ALERT = 'alert';
    const TYPE_MESSAGE = 'message';
    const TYPE_WARNING = 'system';
    const TYPE_DIALOG = 'dialog';
    const TYPE_RELEASED = 'released';
    const TYPE_CANCELED_BY_REMOTE_AGENT = 'canceled_by_remote_agent';

    const DEFAULT_USER = 'SYSTEM';

    const MAILBOX_RECEIVED = 'inbox';
    const MAILBOX_SEND = 'outbox';
    const MAILBOX_SYSTEM = 'system';

    // TODO[Xavi] Segons la configuració del servidor (conf wikiiocmodel) es farà servir un sistema de timers o de websockets
    private $blackboard = []; // Creem un cache per guardar els blackboards carregats

    public function getFileName($userId, $especParams = NULL) {
        $fileName = $this->_notifyFN($userId);
        return $fileName;
    }

    public function getNsTree($currentNode, $sortBy, $onlyDirs = FALSE, $expandProject = FALSE, $hiddenProjects = FALSE, $root = FALSE) {
        throw new UnavailableMethodExecutionException("NotifyDataQuery#getNsTree");
    }

    /**
     * @param array $notificationData el format d'aquest paràmetre és: ['type' => '', 'id' => '', 'title' => '', 'text' => '']
     * @param string $type
     * @param null $id
     * @param null $senderId
     * @return array
     */
    public function generateNotification(array $notificationData, $type = self::TYPE_MESSAGE, $id = NULL, $senderId = NULL, $read = false, $mailbox = self::MAILBOX_RECEIVED) {

        $notification = [];

        $now = new DateTime(); // id
        $timestamp = $now->getTimestamp();

        if ($id === NULL) {
            $id = $timestamp;
        }

        $notification[self::TIMESTAMP] = $timestamp;
        $notification[self::NOTIFICATION_ID] = $id . '_' . $mailbox;
        $notification[self::TYPE] = $type;
        $notification[self::DATA] = $notificationData;

        $notification[self::READ] = $read;
        $notification[self::MAILBOX] = $mailbox;


        // Si no s'ha especificat el sender s'atribueix al sistema
        if ($senderId === NULL) {
            $notification[self::SENDER_ID] = self::DEFAULT_USER;
        } else {
            $notification[self::SENDER_ID] = $senderId;
        }

        return $notification;
    }

    public function update($notificationId, $blackboardId, $dataForUpdate) {

        $this->loadBlackboard($blackboardId);

        $notificationIndex = $this->searchNotificationIndexInBlackboard($notificationId, $this->blackboard[$blackboardId]);

        $notification = $this->blackboard[$blackboardId][$notificationIndex];
        $notification = array_replace($notification, $dataForUpdate);

        $this->blackboard[$blackboardId][$notificationIndex] = $notification;

        $this->saveBlackboard($blackboardId);
    }

    public function delete($notificationId, $blackboardOwnerId)
    {
        $this->loadBlackboard($blackboardOwnerId);
        $notificationIndex = $this->searchNotificationIndexInBlackboard($notificationId, $this->blackboard[$blackboardOwnerId]);

        array_splice($this->blackboard[$blackboardOwnerId], $notificationIndex, 1);
//        unset($this->blackboard[$blackboardOwnerId][$notificationIndex]);
        $this->saveBlackboard($blackboardOwnerId);
    }


    private function searchNotificationIndexInBlackboard($id, $blackboard)  {

        for ($i = 0, $len = count($blackboard); $i < $len; $i++) {
            if ($blackboard[$i][self::NOTIFICATION_ID] == $id) {
                return $i;
            }
        }
        return null;
    }

    public function add($receiverId, $notification, $replaceById=FALSE) {

        $this->loadBlackboard($receiverId);
        if($replaceById){
            $pos = $this->searchNotificationIndexInBlackboard($notification[self::NOTIFICATION_ID], $this->blackboard[$receiverId]);
            if($pos!==NULL){
                $this->blackboard[$receiverId][$pos] = $notification;
            }else{
                $this->blackboard[$receiverId][] = $notification;
            }
        }else{
            $this->blackboard[$receiverId][] = $notification;
        }
        $this->saveBlackboard($receiverId);
    }

    public function get($userId, $since = 0, $deleteContent = TRUE) {

        $messages = $this->getBlackboard($userId);// Alerta[Xavi] PHP copia els arrays per valor, i no per referència

        if ($deleteContent) {
            $this->deleteBlackboard($userId);
        }
        $messages = $this->getMessagesSince($messages, $since);

// ALERTA[Xavi] codi de prova, per generar un avís del sistema que expira en 20 segons
//        $this->delete(WikiGlobalConfig::getConf('system_warning_user', 'wikiiocmodel')); // ALERTA[Xavi] Això només s'ha de descomentar per esborrar la pissara completament
//        $notificationData = ['type' => self::TYPE_WARNING, 'id' => time(), 'title' => WikiGlobalConfig::getConf('system_warning_user', 'wikiiocmodel'), 'text'=>"Prova pel sistema d'avisos del sistema. Ha de sortir una alerta y una notificació llegida"];
//
//        $this->add(WikiGlobalConfig::getConf('system_warning_user', 'wikiiocmodel'),
//            $notificationData,
//            $type = self::TYPE_WARNING,
//            /*id de l'alerta?*/null,
//            WikiGlobalConfig::getConf('system_warning_user', 'wikiiocmodel'),
//            (new DateTime())->getTimeStamp()+20);
        // FI del codi de prova

        $systemGlobalMessages = $this->getSystemGlobalMessages();

        return array_merge($messages, $systemGlobalMessages);
    }

    private function getMessagesSince($messages, $since) {
        $filteresMessages = [];

        for ($i = count($messages)-1; $i >= 0; $i--) {
            if ($messages[$i][self::TIMESTAMP] > $since) {
                $filteresMessages[] = $messages[$i];
            } elseif($messages[$i]!=NULL) {
                break; // totes les notificacions anteriors son més antigues, no cal continuar cercant
            }
        }

        return $filteresMessages;
    }

    private function getSystemGlobalMessages()
    {
        $notifications = [];
        $plugins = ['wikiiocmodel', 'ajaxcommand']; // ALERTA[Xavi] Afegir altres plugins on es trobi la configuració dels missatges


        foreach ($plugins as $plugin) {
            // Comprovem si hi ha un missatge actiu
            $message = WikiGlobalConfig::getConf('system_warning_message', $plugin);

            if (WikiGlobalConfig::getConf('system_warning_show_alert', $plugin) && strlen($message) > 0) {
                $startDate = (new DateTime(WikiGlobalConfig::getConf('system_warning_start_date', $plugin)))->getTimestamp();
                $endDate = (new DateTime(WikiGlobalConfig::getConf('system_warning_end_date', $plugin)))->getTimeStamp();
                $today = (new DateTime())->getTimestamp();
                $type = WikiGlobalConfig::getConf('system_warning_type', $plugin);

                if ($startDate <= $today && $endDate > $today) {
                    $title = WikiGlobalConfig::getConf('system_warning_title', $plugin);
                    if (strlen($title) == 0) {
                        $title = WikiIocLangManager::getLang('system_warning_default_title', $plugin);
                    }

                    $message = WikiGlobalConfig::getConf('system_warning_message', $plugin);
                    $id = hash('md5', $title . $message);
                    $notificationData = ['type' => $type, 'id' => $id, 'title' => $title, 'text' => $message];
                    $sender = WikiGlobalConfig::getConf('system_warning_user', $plugin);
                    $notifications[] = $this->generateNotification($notificationData, self::TYPE_WARNING, $id, $sender, false, self::MAILBOX_SYSTEM);
                }
            }

        }
        return $notifications;
    }


    private function loadBlackboard($userId)
    {
        // Generem el nom
        $filename = $this->getFileName($userId);

        // Carreguem el fitxer
        if (@file_exists($filename)) {
            // Unserialitzem el contingut
            $blackboard = unserialize(io_readFile($filename, FALSE));
        } else {
            // Si no existeix retornem un fitxer amb un array buit
            $blackboard = [];
        }

        //Establim el contingut carregat
        $this->blackboard[$userId] = $blackboard;
    }

    private function saveBlackboard($userId)
    {
        $filename = $this->getFileName($userId);
        $blackboard = $this->getBlackboard($userId);

        if (count($blackboard) > 0) {
            // Serialitzem el contingut del blackboard del usuari
            // Desem el fitxer
            io_saveFile($filename, serialize($blackboard));
        } else {
            // No hi ha res, l'esborrem
            $this->deleteBlackboard($userId);

        }
    }

    // ALERTA[Xavi] el que es retorna es una copia del array, així que els canvis no afectan al cache
    private function getBlackboard($userId)
    {
        if (!isset($this->blackboard[$userId])) {
            // Carreguem el blackboard
            $this->loadBlackboard($userId);
        }

        return $this->blackboard[$userId];
    }

    public function deleteBlackboard($userId)
    {
        // Eliminem el contingut del cache
        unset ($this->blackboard[$userId]);
        // Obtenim el nom del fitxer

        $filename = $this->getFileName($userId);
        // Eliminem el fitxer

        @unlink($filename);
    }

    private function _notifyFN($user)
    {
        $dir = WikiGlobalConfig::getConf("notificationdir");
        return $dir . '/' . md5(cleanID($user)) . '.blackboard';
    }
}

