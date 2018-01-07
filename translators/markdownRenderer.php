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

    function emphasis($text) {
        $this->doc .= '//' . $text . '//';
    }

    function underline($text) {
        $this->doc .= '__' . $text . '__';
    }

    function monospace($text) {
        $this->doc .= "''" . $text . "''";
    }

    function deleted($text) {
        $this->doc .= "<del>" . $text . "</del>";
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

    function emphasis($text) {
        $this->doc .= '*' . $text . '*';
    }

    function underline($text) {
        $this->doc .= '<ins>' . $text . '</ins>';
    }

    function monospace($text) {
        $this->doc .= '`' . $text . '`';
    }

    function deleted($text) {
        $this->doc .= "~~" . $text . "~~";
    }
}
