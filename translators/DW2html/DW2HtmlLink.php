<?php
require_once "DW2HtmlParser.php";

class DW2HtmlLink extends DW2HtmlMarkup {


    protected function getContent($token) {

        // TODO: Implementar els links interns
        return $this->getExternalLink($token);
    }

    private function getExternalLink($token) {
        $urlPattern = "/\[\[(.*?)[#|]/";
        $anchorPattern = "/#(.*?)[|\]]/";
        $textPattern = "/\|(.*?)[|\]]/";

        preg_match($urlPattern, $token['raw'], $matchUrl);
        $url = $matchUrl[1];
        $anchor = false;

        // Aquest és opcional
        if (preg_match($anchorPattern, $token['raw'], $matchAnchor)) {
            $anchor = $matchAnchor[1];
        }

        if (preg_match($textPattern, $token['raw'], $matchText)) {
            $text = $matchText[1];
        } else {
            $text = $url;
        }

        $value = 'href="' . $url;
        if ($anchor) {
            $value .= '#' . $anchor;
        }
        $value .= '">';

        $value .= $text;

        // És selfcontained, no hi ha tancament!

        return $this->getReplacement(self::OPEN) . $value . $this->getReplacement(self::CLOSE);
    }

}