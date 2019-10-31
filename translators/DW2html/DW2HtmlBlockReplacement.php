<?php
require_once "DW2HtmlParser.php";

class DW2HtmlBlockReplacement extends DW2HtmlBlock{

//    public $closed = FALSE;
//
//    protected function getReplacement($position) {
//
//        if ($this->closed) {
//            die ("ja estava closed");
//            return '';
//        } else {
//            return parent::getReplacement($position);
//        }
//
//    }

    protected function getContent($token) {


        $this->getPreAndPost($pre, $post);


        return $pre . $this->extra['replacement'] . $post;

    }



//    protected function resolveOnClose($result) {

//        $this->closed = TRUE;
//        return parent::resolveOnclose($result);
//    }

}