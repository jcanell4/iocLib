<?php
require_once "DW2HtmlParser.php";

class DW2HtmlLink extends DW2HtmlInstruction {


    public function open() {

        $token = $this->currentToken;
        $ns = FALSE;
        $url = $this->extractUrl($token, $ns);

        $anchorPattern = "/#(.*?)[|\]]/";
        $textPattern = "/\|(.*?)[|\]]/";

        $anchor = FALSE;

        // Aquest és opcional
        if (preg_match($anchorPattern, $token['raw'], $matchAnchor)) {
            $anchor = $matchAnchor[1];
        }

        if (preg_match($textPattern, $token['raw'], $matchText)) {
            $text = $matchText[1];
        } else {
            $text = $url;
        }

        return $this->makeTag($url, $anchor, $text, $ns);
    }

    private function extractUrl($token, &$ns) {
        // els noms d'enllaç de la wiki no admeten punts, així que aquesta comprovació és suficient
        $patternIsExternal = "/\[\[.*?\..*?\|/";

        if (preg_match($patternIsExternal, $token['raw'])) {

            $urlPattern = "/\[\[(.*?)[#|]/";
            preg_match($urlPattern, $token['raw'], $matchUrl);
            $url = $matchUrl[1];
        } else {
            $urlPattern = "/\[\[(.*?)\|/";
            preg_match($urlPattern, $token['raw'], $matchUrl);
            $ns = $matchUrl[1];
            $url = '/dokuwiki_30/doku.php?id=' . $matchUrl[1]; // TODO: D'on extraiem la urlbase? ALERTA! Això no funcionarà en producció!
        }

        return $url;

    }

    private function makeTag($url, $anchor = NULL, $text, $ns) {
        $value = 'href="' . $url;
        if ($anchor) {
            $value .= '#' . $anchor;
        }

        $value .= '"';
        // fi de la url

        // altres atributs
        if ($ns !== FALSE) {
            $value.= ' data-dw-type="internal_link"';
            $value.= ' data-dw-ns="' . $ns . '"';
        }
        $value.= ' title="' . $text . '"';

        // Tancament de la etiqueta img;
        $value .= '>' . $text;

        return $this->getReplacement(self::OPEN) . $value . $this->getReplacement(self::CLOSE);
    }
}