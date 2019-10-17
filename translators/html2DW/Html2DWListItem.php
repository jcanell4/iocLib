<?php
require_once "Html2DWParser.php";

class Html2DWListItem extends Html2DWMarkup {

    protected function getContent($token) {

        $count = count(static::$stack);

        // Un LI només pot trobar-se dins d'un ul o ol, per tant forçosament el nombre d'elemens ha de ser 2 o superior (el contenidor i aquest item)

        $previous = static::$stack[$count - 2];

        $character = "";

        switch ($previous['list']) {
            case 'ul':
                $character = '*';
                break;

            case 'ol':
                $character = '-';
                break;

            default:
//                var_dump(static::$stack);
//                die();
                $character = 'Tipus de llista desconeguda >>' . $previous['list'] . '<<';

        }

//        if (strpos($token['value'],"Subitem") !== false) {
//            var_dump($this->currentToken);
//            die();
//        }

        // El $open només ha d'incloure això si aquest content és el primer del list item. Es pot afegir un comptador d'elements al currentToken??

        if (!$this->currentToken['is-first-chunk']) {
            $this->currentToken['is-first-chunk'] = true;
            $open = str_repeat(' ', $previous['level'] * 2) . $character . ' ';
        } else {
            $open ='';
        }

        return $open . $token['value'];
    }

}