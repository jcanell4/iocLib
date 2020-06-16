<?php
require_once "Html2DWParser.php";

class Html2DWNewContent extends Html2DWMarkup {


    protected function getContent($token) {

        preg_match_all($token['pattern'], $token['raw'], $matches);
        $content = $this->parseContent($matches[1][0]);


        // Només s'ha de deixar un salt de línia final, eliminem els posibles duplicats

        $lastIndex = strlen($content) - 2;

        while ($lastIndex >= 0 && substr($content, $lastIndex, 1) === "\n") {
            $lastIndex--;
        }

        if ($lastIndex >= 0 && $lastIndex + 2 < strlen($content)) {
            $content = substr($content, 0, $lastIndex + 2);
        }


        return $this->getReplacement(self::OPEN) . $content . $this->getReplacement(self::CLOSE);

    }

}