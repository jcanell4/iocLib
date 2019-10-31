<?php
require_once "DW2HtmlParser.php";

class DW2HtmlContent extends DW2HtmlInstruction {


    protected $value;


    protected function getReplacement($position) {

        $p = parent::getReplacement($position);

        // TODO: Si no hi ha $prev aquest content s'ha d'afegir automàticament dins d'un paràgraf


        $prev = $this->getPreviousState();

        // Si no hi ha previous llavors aquest contingut s'ha de ficar dins d'un paràgraph
        if (!$prev) {
            // ALERTA! els tags han d'anar a la configuració del token
            if ($position == self::OPEN) {
                $p = '<p>' . $p;
            } else {
                $p .= "</p>\n";
            }

//            die("ha entrat");
        }

//        die("reconegut");

        return $p;
    }

    protected function getContent($token) {


        $value = $token['value'];

        $this->value = $value;



        return $this->getReplacement(self::OPEN) . $value . $this->getReplacement(self::CLOSE);
    }

    protected function resolveOnClose($field) {

        $value = $field . $this->getReplacement(self::CLOSE);
//        var_dump(static::$stack, $this->currentToken);
//        die();


        return $value;
//        return $field . $this->getReplacement(self::CLOSE);
    }
}