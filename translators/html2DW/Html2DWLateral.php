<?php
require_once "Html2DWParser.php";

class Html2DWLateral extends Html2DWMarkup {

    protected function getContent($token) {
        preg_match_all($token['pattern'], $token['raw'], $matches);

//        die("stop");
        // 0 raw
        // 1 és el tipus
        // 2 és el contingut

        $type = $matches[1][0];
        $content = $matches[2][0];


        switch ($type) {
            case "image":

                $fullUrl = $this->extractVarName($content, 'src');
                $url = explode('=', $fullUrl)[1];

                $text = $this->extractVarName($content, 'title');

                $text = $this->parseContent($text);

                return '{{' . $url . '?200|' . $text . '}}';


                break;

            default:
                return ' ** NO IMPLEMENTAT ** ';
        }

    }


}
