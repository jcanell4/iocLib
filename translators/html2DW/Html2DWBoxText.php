<?php
require_once "Html2DWParser.php";

class Html2DWBoxText extends Html2DWBox {

    protected function getContent($token) {
        preg_match_all($token['pattern'], $token['raw'], $matches);

        $data = $this->getBoxInfo($matches[2][0]);

        $type = $this->extractVarName($token['raw'], 'data-dw-box-text');

        // [Xavi] Això no té sentit, el large és controla a sota i el textl no s'afegeix al data-dw-type
        // no hi ha aquest atribut en aquests elements
//        if ($this->extractVarName($token['raw'], 'data-dw-type', false)) {
//            $data[] = 'large';
//        }

        // El grup 1 es el tipus
        // el grup 2 es la resta del contingut: camps + content


//        <div class="ioctext" data-dw-box="text">
//            <div class="ioccontent">
//                <p class="ioctitle" data-ioc-optional>títol text</p>
//                <p class="editable-text">Incloure la sintaxis de text 1</p>
//                <p class="editable-text">Incloure la sintaxis de text 2</p>
//                <p class="editable-text">Incloure la sintaxis de text 2</p>
//            </div>
//        </div>


        if ($type === "textl") {
            $type = "text";
            $data['large'] = '';
        }


        $pre = '::' . $type . ":\n";

        foreach ($data as $key => $value) {
            $pre .= '  :' . $key . ':' . $value . "\n";
        }

        //poden ser múltiples paràgrafs

        $start = strpos($matches[2][0], '<p class="editable-text"');


        $content = $this->normalize(substr($matches[2][0], $start));


        // [Xavi] per poder capturar correctament les caixes hem afegit el tancament de paràgraf
        // corresponent al final del contingut, de manera que queda </p></div></div>
        // això fa que el parse del content sigui incorrecte, perquè s'ha perdut
        $content = $this->parseContent($content);



        $post = ":::\n";

        if (substr($content, -2, 2) === "\n\n") {
            $content = substr($content, 0, -1);
        }
        if (substr($content, -1, 1) !== "\n") {
            $post = "\n" . $post;
        }

        return $pre . $content . $post;
    }

    // Eliminem els salts de línia \n i els <br> ja que són afegits per l'editor.
    protected function normalize($raw) {
        $content = str_replace("\n", '', $raw);

        $content = preg_replace('/<br ?\/?>/', '', $content);

        return $content;
    }

    protected function getBoxInfo($text) {

        $data = [];

        $pattern = '/<p(?: class=".*?")? data-dw-field="(.*?)".*?>(.*?)<\/p>/m';

        preg_match_all($pattern, $text, $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            $data[$matches[1][$i]] = $matches[2][$i];
        }


        return $data;
    }
}
