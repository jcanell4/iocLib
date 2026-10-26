<?php
/**
 * Description of WebsocketNotifyModel
 * @author Xavier García <xaviergaro.dev@gmail.com>
 */
if (!defined("DOKU_INC")) die();
require_once DOKU_INC . "inc/media.php";
require_once DOKU_INC . "inc/pageutils.php";
require_once DOKU_INC . "inc/common.php";

class WebsocketNotifyModel extends DokuNotifyModel
{
    protected $type = 'websocket';

    // Aquest es l'únic mètode necessari quan es tracta de websockets
    public function init()
    {
        $init['type'] = $this->type;
        $init['ip'] = WikiGlobalConfig::getConf('notifier_ws_ip', 'wikiiocmodel');
        $init['port'] = WikiGlobalConfig::getConf('notifier_ws_port', 'wikiiocmodel');
        $init['token'] = 'TODO: generar token secret';// TODO[Xavi] El toke s'ha de fer servir per autenticar al usuari a través del websocket, fer servir el mateix que sectok o un altre diferent?

        return $init;
    }

    public function notifyMessageToFrom($text, $receiverId, $senderId = NULL, $mailbox, $read = false)
    {
        throw new UnavailableMethodExecutionException("DokuNotifyModel#notifyToFrom");
    }

    public function notifyTo($data, $receiverId, $type, $id=NULL, $mailbox)
    {
        throw new UnavailableMethodExecutionException("DokuNotifyModel#notifyToFrom");
    }

    public function popNotifications($userId, $since)
    {
        throw new UnavailableMethodExecutionException("WebsocketNotifyModel#popNotifications");
    }

    public function close($userId)
    {
        throw new UnavailableMethodExecutionException("WebsocketNotifyModel#close");
    }


    public function update($notificationId, $blackboardId, $updatedData)
    {
        // TODO: Implement update() method.
    }

    public function delete($notificationId, $blackboardId)
    {
        // TODO: Implement delete() method.
    }
}
