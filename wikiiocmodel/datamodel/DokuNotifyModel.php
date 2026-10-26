<?php
/**
 * Description of DokuNotifyModel
 * @author Xavier García <xaviergaro.dev@gmail.com>
 */
if (!defined("DOKU_INC")) die();
require_once DOKU_INC . "inc/media.php";
require_once DOKU_INC . "inc/pageutils.php";
require_once DOKU_INC . "inc/common.php";

abstract class DokuNotifyModel extends AbstractWikiModel
{
    const TYPE_ALERT = 'alert';
    const TYPE_MESSAGE = 'message';
    const TYPE_WARNING = 'system';
    const TYPE_DIALOG = 'dialog';
    const TYPE_RELEASED = 'released';
    const TYPE_CANCELED_BY_REMOTE_AGENT = 'canceled_by_remote_agent';

    const MAILBOX_RECEIVED = 'inbox';
    const MAILBOX_SEND = 'outbox';
    const MAILBOX_SYSTEM = 'system';

    protected $type = 'abstract';
    protected $dataQuery;

    public function __construct($persistenceEngine=NULL)
    {
        parent::__construct($persistenceEngine);
        // TODO[Xavi] Segons la configuració del wikiioc model farem servir el NotifyDataQuery o el WebSocketConnection
        if($persistenceEngine){
            $this->dataQuery = $persistenceEngine->createNotifyDataQuery();
        }else{
            $this->dataQuery = new NotifyDataQuery();
        }
    }

    public function getData() {
        // TODO: Implement getData() method.
        throw new UnavailableMethodExecutionException("DokuNotifyModel#getData");
    }

    public function setData($toSet) {
        // TODO: Implement setData() method.
        throw new UnavailableMethodExecutionException("DokuNotifyModel#setData");
    }

    public abstract function init();

    public abstract function notifyMessageToFrom($text, $receiverId, $senderId = NULL, $mailbox, $read = false);

    public abstract function notifyTo($data, $receiverId, $type, $id=NULL, $mailbox);

    public abstract function popNotifications($userId, $since);

    public abstract function close($userId);

    public abstract function update($notificationId, $blackboardId, $updatedData);

    public abstract function delete($notificationId, $blackboardId);

    public function getConstClass(){
        return get_class($this->dataQuery);
    }
}
