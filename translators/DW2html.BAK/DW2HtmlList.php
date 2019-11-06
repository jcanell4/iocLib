<?php
require_once "DW2HtmlParser.php";

class DW2HtmlList extends DW2HtmlBlock {



    protected function getReplacement($position) {

        $ret = parent::getReplacement($position);


        switch ($position) {
            case IocInstruction::OPEN:
                return '<' . $this->extra['container'] . ">\n";

            case IocInstruction::CLOSE:
                return '</' . $this->extra['container'] . ">\n";
//
        }

        return 'ERROR: unknown position: ' . $position;
    }

}