<?php
/**
 * SendListToUsersAction: Envia una notificació i un missatge als destinataris especificats
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class SendListToUsersAction extends NotifyAction {

    protected function responseProcess() {
        $this->params['message'] .= ".<br>Llista de projectes: ";
        $checked_items = json_decode($this->params['checked_items'], true);
        foreach ($checked_items as $ns) {
            $this->params['message'] .= "<br>- <a href=".DOKU_URL."doku.php?id=$ns>$ns</a>";
        }
        $this->params["data-call"] = "selected_projects&grups=".str_replace('"', "'", $this->params['grups']);

        $response['notifications'] = [];
        $notifyResponse = $this->notifyMessageToFrom();
        $response['notifications'][] = $notifyResponse['notifications'];
        $response['info'] = $notifyResponse['info'];
        return $response;
    }

}
