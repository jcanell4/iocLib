<?php
require_once "DW2HtmlParser.php";

class DW2HtmlSound extends DW2HtmlInstruction {


    public function open() {
        $token = $this->currentToken;

        // Descartem el segón paràmetre, la clau no es fa servir

        $id = '';

        if (preg_match($token['pattern'], $token['raw'], $match)) {
            $id = $match[1];
        }

        $html = '<div data-dw-block="sound" data-sound-id="' . $id . '" data-ioc-id="ioc_sound_' . $id . '" contenteditable="false">' .
            '<iframe width="100%" height="20" scrolling="no" frameborder="no" src="https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/' . $id . '?secret_token=none&color=%230066cc&inverse=false&auto_play=false&show_user=true"></iframe>' .
            '</div>';

        return $html;
    }


}