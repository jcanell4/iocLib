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

        // Si hi ha un salt de línia forçat al final de la línia l'eliminem, aquest cas no s'ha de donar mai normalment
//        $test = substr($innerContent, 0, strlen($innerContent)-6);
//        if (substr($innerContent, -6) === '<br />') {
//            $innerContent = substr($innerContent, 0, strlen($innerContent)-6);
//        }




//        $this->parsingContent = true;
        $innerContent = trim($this->parseContent($innerContent));
//        $this->parsingContent = false;

        // Si el primer element del $innerContent no és < es que es tractava de content i per tant s'ha d'embolcallar en un paràgraph

        // Tots els elements parsejats hand de començar per <div o <p

        // PROBLEMA! peta si hi ha un element com un enllaç al començament d'una línia i s'han fet reemplaços de salts de línia
        // això s'ha de solucionar abans, els salts de línia s'han dhaver afegit abans
        //
        if (strpos($innerContent, '<') !== 0) {
            $innerContent = '<p>' . $innerContent . '</p>';
        }
//        if (strpos($innerContent, '<div') !== 0 || strpos($innerContent, '<p') !== 0) {
//            $innerContent = '<p>' . $innerContent . '</p>';
//        }

        if (substr($innerContent, strlen($innerContent)-1) !== "\n") {
            $innerContent .= "\n";
        }


        return "<newcontent>\n" . $innerContent . "</newcontent>\n";
    }

    public function isClosing($token) {

        return !$this->parsingContent;

    }

    // @override ALERTA! La diferència es que es tracta el contingut parsejat com si es trobès a l'arrel en lloc de ser intern,
    // en cas contrari no es tancan correctament algunes etiquetes
    protected function parseContent($raw, $setInner = true) {
        ++static::$instancesCounter;
        $class = static::$parserClass;
//        $isInnerPrevious = $class::isInner();
//        $class::setInner(true);

        $content = $class::getValue($raw);

//        $class::setInner($isInnerPrevious);

        --static::$instancesCounter;
        return $content;
    }
}