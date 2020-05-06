<?php
require_once "Html2DWParser.php";

class Html2DWMedia extends Html2DWMarkup {


    protected function getContent($token) {

        // No es tracta d'una URL si no d'una referencia
        $id = $this->extractVarName($token['raw'], 'data-video-id');

        $id = explode('?', $id)[0];
        $id = explode('|', $id)[0];


        $type = $this->extractVarName($token['raw'], 'data-video-type');
        $size = $this->extractVarName($token['raw'], 'data-video-size', false);
        $title = $this->extractVarName($token['raw'], 'data-video-title', false);

        $html = '{{' . $type . '>' . $id;
        $html .= ($size ? '?' . $size : '');
        $html .= ($title ? '|' . $title : '');
        $html .= '}}';

        return $html;

    }

}