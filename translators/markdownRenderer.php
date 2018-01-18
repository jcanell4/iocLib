<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MarkDownRender
 *
 * @author josep
 */
class MarkDownWikiRenderer extends Doku_Renderer{
    private $starting;
    
    function cdata($text) {
        $this->doc .= $text;
    }
    
    function eol($text) {
        if($this->starting){
            $this->starting=FALSE;
        }else{
            $this->doc .= "\n";
        }
    }
    
    function document_start() {
        $this->starting=TRUE;
    }

    function document_end() {
        $this->doc = substr($this->doc, 0, -1);
    }
}

class MarkDown2DokuWikiRender extends MarkDownWikiRenderer{
    function header($text, $level, $pos) {
        $rep = 7 -$level;
        $this->doc .= str_repeat("=", $rep)." ".$text." ".str_repeat("=", $rep);
    }

    function strong_open() {
        $this->doc .= '**';
    }

    function strong_close(){
        $this->doc .= '**';
    }

    function emphasis_open() {
        $this->doc .= '//';
    }

    function emphasis_close(){
        $this->doc .= '//';
    }

    function underline_open() {
        $this->doc .= '__';
    }

    function underline_close() {
        $this->doc .= '__';
    }

    function monospace_open() {
        $this->doc .= "''";
    }

    function monospace_close() {
        $this->doc .= "''";
    }

    function deleted_open() {
        $this->doc .= "<del>";
    }
    function deleted_close() {
        $this->doc .= "</del>";
    }

    function subscript_open() {
        $this->doc .= "<sub>";
    }

    function subscript_close() {
        $this->doc .= "</sub>";
    }

    function superscript_open() {
        $this->doc .= "<sup>";
    }

    function superscript_close() {
        $this->doc .= "</sup>";
    }
}

class DokuWiki2MarkDownRender extends MarkDownWikiRenderer{
    private $headers = array();
    
    function header($text, $level, $numLevels) {
        $strNum = "";
        for($i=0; $i<$level; $i++){
            $strNum .= $numLevels[$i].".";
        }
        $this->doc .= str_repeat("#", $level).$strNum." ".$text." {#".sectionID($text,$this->headers)."}";
    }

    function emphasis_open() {
        $this->doc .= '*';
    }

    function emphasis_close() {
        $this->doc .= '*';
    }

    function underline_open() {
        $this->doc .= '+';
    }

    function underline_close() {
        $this->doc .= '+';
    }

    function monospace_open() {
        $this->doc .= '`';
    }

    function monospace_close() {
        $this->doc .= '`';
    }

    function deleted_open() {
        $this->doc .= "~~";
    }

    function deleted_close() {
        $this->doc .= "~~";
    }

    function subscript_open() {
        $this->doc .= "<sub>";
    }

    function subscript_close() {
        $this->doc .= "</sub>";
    }

    function superscript_open() {
        $this->doc .= "<sup>";
    }

    function superscript_close() {
        $this->doc .= "</sup>";
    }
}
