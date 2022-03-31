<?php
/**
 * RefreshMoodleSessionAction: Fa una crida a un webservice de Moodle per generar el refresc de la sessió a Moodle
 * @author rafael
 */
if (!defined("DOKU_INC")) die();

class RefreshMoodleSessionAction  extends AbstractWikiAction {

    public function responseProcess() {
        $ws = new WsMoodleSession();
        $ws->setToken($this->params['moodleToken']);
        $ret = $ws->refreshUserSession($_SERVER['REMOTE_USER']);
        return $ret;
    }

}
