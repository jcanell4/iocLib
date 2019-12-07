<?php
require_once "Html2DWParser.php";

class Html2DWSound extends Html2DWMarkup {


    protected function getContent($token) {

        // No es tracta d'una URL si no d'una referencia
        $id = $this->extractVarName($token['raw'], 'data-sound-id');

        $html = '{{soundcloud>' . $id . ':clau}}';
        return $html;

    }

}