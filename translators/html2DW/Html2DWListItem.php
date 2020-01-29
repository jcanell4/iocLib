<?php
require_once "Html2DWParser.php";

class Html2DWListItem extends Html2DWMarkup {

    public function getTokensValue($tokens, &$tokenIndex) {


        $content = parent::getTokensValue($tokens, $tokenIndex);

        //var_dump($token, $this->getTopState(), $content);
        //die('passa pel getTokensValue');

        //el que hi ha al getcontent ha de fer-se aqí
        $level = $this->getLevel();

        $pre = str_repeat(' ', $level * 2) . $this->getCharacter() . ' ';

        // L'últim caràcter serà un salt de línia quan es tracti de lliste imbricades
        $test = substr($content, -2, 2);

//        if (substr($content, -2, 2) != "\n\n") {
//            $post = "\n";
//        } else
////            {
////            $post = "";
////        }

        if (substr($content, -1, 1) != "\n") {
            $post = "\n";
        } else {
            $post = "";
        }

        return $pre . $content . $post;
    }

    protected function getLevel() {

        return $this->getTopListNode()['level'];
    }

    protected function getCharacter() {


        $listNode = $this->getTopListNode();

        switch ($listNode['list']) {
            case 'ul':
                $character = '*';
                break;

            case 'ol':
                $character = '-';
                break;

            default:
//                var_dump(static::$stack);
//                die();
                $character = 'Tipus de llista desconeguda >>' . $listNode['list'] . '<<';

        }

        return $character;
    }

    protected function getTopListNode() {

        for ($i = count(static::$stack) - 1; $i>=0; $i--) {
            if (static::$stack[$i]['state'] == 'list') {
                return static::$stack[$i];
            }
        }

    }

    protected function getReplacement($position) {

//        $prev = $this->getPreviousState();
//
//        if ($prev['skip-close'] && $position = self::CLOSE) {
//            return '';
//        }


        if (static::DEBUG_MODE) {
            return $this->getDebugReplacement($position);
        } else {
            return is_array($this->extra['replacement']) ? $this->extra['replacement'][$position] : $this->extra['replacement'];
        }
    }

    protected function resolveOnClose($result) {

        // quan es crida? a que tenim accéss?

        $ret = parent::resolveOnClose($result);

        return $ret;
    }
}