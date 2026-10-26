<?php
/**
 * Description of TimerLockModel
 * @author Xavier García <xaviergaro.dev@gmail.com>
 */
if (!defined("DOKU_INC")) die();
require_once DOKU_INC . "inc/media.php";
require_once DOKU_INC . "inc/pageutils.php";
require_once DOKU_INC . "inc/common.php";

class TimerNotifyModel extends DokuNotifyModel
{
    protected $type = 'ajax';
    protected $dataQuery;

    public function getData() {
        throw new UnavailableMethodExecutionException("DokuNotifyModel#getData");
    }

    public function setData($toSet) {
        // TODO: Implement setData() method.
        throw new UnavailableMethodExecutionException("DokuNotifyModel#setData");
    }

    public function init()
    {

        $init['type'] = $this->type;
        $init['timer'] = WikiGlobalConfig::getConf('notifier_ajax_timer', 'wikiiocmodel') * 1000;
        return $init;
    }

    public function notifyMessageToFrom($data, $receiverId, $senderId = NULL, $mailbox, $read= false)
    {
        // Posa el missatge text a la cua d'enviaments de l'usuari receiverId i firma el missatge amb el nom indicat a
        // sender. En el sistema de WebSockets el missatge s'envia de forma immediata al client. En el cas de Timers,
        // s'emmagatzema a la pissarra de l'usuari receiverId.


        // L'afegim al blackboard del destinatari ($receiverId, $notificationData, $type = self::TYPE_MESSAGE, $id=NULL, $senderId = NULL)

        $notification= $this->dataQuery->generateNotification($data, 'message', null, $senderId, $read, $mailbox);

        $this->dataQuery->add($receiverId, $notification);

        return $notification;
    }

    public function notifyTo($data, $receiverId, $type, $id=NULL, $mailbox)
    {
        // Posa el missatge text a la cua d'enviaments de l'usuari receiverId i firma el missatge amb el nom indicat a
        // sender. En el sistema de WebSockets el missatge s'envia de forma immediata al client. En el cas de Timers,
        // s'emmagatzema a la pissarra de l'usuari receiverId.

        // L'afegim al blackboard del destinatari ($receiverId, $notificationData, $type = self::TYPE_MESSAGE, $id=NULL, $senderId = NULL)
        $notification= $this->dataQuery->generateNotification($data, $type, $id, $senderId, FALSE, $mailbox);
        return $this->dataQuery->add($receiverId, $notification, TRUE);
    }

    public function popNotifications($userId, $since = 0)
    {
        // Aquest mètode només té sentit en el sistema de Timers per tal que es pugui retornar el contingut de la
        // pissarra de l'usuari actiu. En el cas de WebSockets, no es cridarà mai, ja que el mètode notifyToFrom fa
        // l'enviament de forma immediata. El mètode  popNotifications, a més de retornar el contingut, elimina també
        // la pissarra consultada.
        if($since==NULL){
            $since=0;
        }
        return $this->dataQuery->get($userId, $since, false);
    }

    public function close($userId)
    {
        // Tanca la sessió i el sistema (per exemple els sockets)
        // ALERTA[Xavi] Tancar la sessió? No es fa servir

    }


    public function update($notificationId, $blackboardId, $updatedData)
    {
        return $this->dataQuery->update($notificationId, $blackboardId, $updatedData);
    }

    public function delete($notificationId, $blackboardId)
    {
        return $this->dataQuery->delete($notificationId, $blackboardId);
    }

}
