<?php
/**
 * SendListToUsersAction: Envia una notificació i un missatge als destinataris especificats
 * @culpable <rclaver@xtec.cat>
 */
if (!defined("DOKU_INC")) die();

class SendListToUsersAction extends NotifyAction {

    protected function responseProcess() {
        $this->params['to'] = trim($this->params['users'], ",");
        $this->params['message'] .= ". Enllaç a la pàgina amb la llista de projectes filtrats.";
        $this->params["data-call"] = "selected_projects&grups=".str_replace('"', "'", $this->params['grups']);

        $response['notifications'] = [];
        $notifyResponse = $this->notifyMessageToFrom();
        $response['notifications'][] = $notifyResponse['notifications'];
        $response['info'] = $notifyResponse['info'];
        return $response;
    }

}
