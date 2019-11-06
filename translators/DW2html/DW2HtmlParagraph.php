<?php

if (!defined('DOKU_INC')) die();
require_once DOKU_INC . 'lib/lib_ioc/iocparser/IocInstruction.php';

class DW2HtmlParagraph extends DW2HtmlInstruction {




    public function isClosing($token) {
        // un doble salt de línia sempre tanca un paràgraf

        if ((isset($token['extra']) && $token['extra']['block'] === TRUE && $token['action'] == 'open')
            || $token['raw'] === "\n\n") {

//            var_dump($token);
//
//            echo "tancant grup perque... ";
//
//            if (isset($token['extra']) && $token['extra']['block'] && ($token['action'] == 'open')) {
//                echo "el nou token és un block\n";
//            } else if ($token['raw'] == "\n\n") {
//                echo "el nou token és un salt de línia doble\n";
//            }


            return true;
        } else {
//            var_dump($token);


//            echo "NO CAL TANCAR EL GRUP PERQUE ...";


//            if (isset($token['extra']) && !$token['extra']['block']) {
//                echo "el nou token NO és un block\n";
//            } else if (($token['action'] != 'open')) {
//                echo "el nou token NO és open\n";
//            } else if ($token['raw'] != "\n\n") {
//                echo "el nou NO token és un salt de línia doble\n";
//            }

//            die ("fi isclosing");
            return false;
        }


//      var_dump($token);
//      die ("fi isclosing");
//        return true;
    }
}
