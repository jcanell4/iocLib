<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBlock extends DW2HtmlInstruction {


    protected $value;

    protected function getContent($token) {

        $value = $token['value'];

        $this->value = $value;

        return $this->getReplacement(self::OPEN) . $value;
    }

    protected function resolveOnClose($field) {

        $value = $field . $this->getReplacement(self::CLOSE);
//        var_dump(static::$stack, $this->currentToken);
//        die();



        return $value;
//        return $field . $this->getReplacement(self::CLOSE);
    }

    protected function getPreAndPost(&$pre, &$post) {

        $prev = static::getPreviousState();

        $pre = $post = '';

        if ($prev && $prev['state'] === 'paragraph') {

            $pre = $prev['instruction']->resolveOnClose('');

            // Si es tanca abans s'ha d'obrir desprès
            $post = $prev['instruction']->getReplacement(self::OPEN);

        }

    }
}