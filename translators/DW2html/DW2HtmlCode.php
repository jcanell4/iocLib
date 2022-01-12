<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlCode extends DW2HtmlInstruction {

//    protected $newLinefound = '';

//    protected function getReplacement($position) {
//
//        $ret = parent::getReplacement($position);
//
//        if ($position === IocInstruction::CLOSE) {
//            $ret .= $this->newLinefound;
//        }
//
//        return $ret;
//    }

    public function close() {
        return '';
    }

    public function open() {

//        var_dump($this->currentToken);
//        die('open code');
        $token = $this->currentToken;
        // El contingut dintre d'aquest block no parseja, es deixa tal qual


        if (preg_match($token['pattern'], $token['raw'], $match)) {
            $value = $match[1];
        } else {
            $value = "ERROR: No s'ha trobat coincidencia amb el patró";
        }



        // Si l'últim caràcters és un salt de línia l'eliminem, això es necessari perque el salt de línia no
        // ha de forma part del contingut

        if (substr($value, -1) == "\n") {
            $value = substr($value, 0, strlen($value) - 1);
        }

        if ($token['extra']['padding']) {
            $pattern = "/^ {" . $token['extra']['padding'] . "}/m";
            $value = preg_replace($pattern, '', $value);
        }

        // Si hi ha un llenguatge ho posem com atribut

        $pattern = "/<.*? (.*?)>/";

        $openReplacement = $this->getReplacement(self::OPEN);

        if (preg_match($pattern, $token['raw'], $match)) {

//            var_dump(end(static::$stack));
//            die();

            $lang = $match[1];

//            var_dump($lang);
//            die();

            $openReplacement = static::AddAttributeToTag($openReplacement, 'data-dw-lang', $lang);
        }


        $value = $openReplacement . $value . $this->getReplacement(self::CLOSE);
//        var_dump($value);
//        die();

//        $this->getPreAndPost($pre, $post);


        return $value;
//        return $pre . $value . $post;
    }


}
