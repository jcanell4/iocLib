<?php
require_once "DW2HtmlParser.php";

class DW2HtmlNewContent extends DW2HtmlInstruction {

    protected $parsingContent = false;

    public function open() {
        $token = $this->currentToken;

        // Descartem el segón paràmetre, la clau no es fa servir

        $innerContent = '';


        if (preg_match($token['pattern'], $token['raw'], $match)) {
            // Cal eliminar els salts de línia inicials si es troba algun perquè si no es produeix un error de tancament

            $innerContent = $match[1];

            $firstTokenIndex = 0;
            while ($firstTokenIndex < strlen($innerContent) && substr($innerContent, $firstTokenIndex, 1) === "\n") {
                $firstTokenIndex++;
            }

            $innerContent = substr($innerContent, $firstTokenIndex);

        }

        $this->parsingContent = true;
        $innerContent = trim($this->parseContent($innerContent));
        $this->parsingContent = false;

        // Si el primer element del $innerContent no és < es que es tractava de content i per tant s'ha d'embolcallar en un paràgraph
        if (strpos($innerContent, '<') !== 0) {
            $innerContent = '<p>' . trim($innerContent) . '</p>';
        }

        return "<newcontent>\n" . $innerContent . "\n</newcontent>\n";
    }

    public function isClosing($token) {

        return !$this->parsingContent;

    }

}