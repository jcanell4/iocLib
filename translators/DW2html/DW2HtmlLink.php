<?php
require_once "DW2HtmlParser.php";

class DW2HtmlLink extends DW2HtmlMarkup {


    protected function getContent($token) {

        $url = $this->extractUrl($token);

        $anchorPattern = "/#(.*?)[|\]]/";
        $textPattern = "/\|(.*?)[|\]]/";

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

        return $this->makeTag($url, $anchor, $text);
    }

    private function extractUrl($token) {
        // els noms d'enllaç de la wiki no admeten punts, així que aquesta comprovació és suficient
        $patternIsExternal = "/\[\[.*?\..*?\|/";

        if (preg_match($patternIsExternal, $token['raw'])) {

            $urlPattern = "/\[\[(.*?)[#|]/";
            preg_match($urlPattern, $token['raw'], $matchUrl);
            $url = $matchUrl[1];
        } else {
            $urlPattern = "/\[\[(.*?)\|/";
            preg_match($urlPattern, $token['raw'], $matchUrl);
            $url = '/dokuwiki_30/doku.php?id=' . $matchUrl[1]; // TODO: D'on extraiem la urlbase?
        }

        return $url;

    }

    private function makeTag($url, $anchor = NULL, $text) {
        $value = 'href="' . $url;
        if ($anchor) {
            $value .= '#' . $anchor;
        }

        $value .= '">';
        $value .= $text;

        return $this->getReplacement(self::OPEN) . $value . $this->getReplacement(self::CLOSE);
    }
}