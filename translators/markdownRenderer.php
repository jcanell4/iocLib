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

    function listu_open() {}

    function listu_close() {}

    function listo_open() {}

    function listo_close() {}

    function listitem_open($level) {
        // TODO[Xavi] s'han d'afegir 2 espais per nivell, però el nivell per ara no es calcula
        $this->doc .= "  * ";
    }

    function listitem_close() {
        $this->doc .= "\n";
    }

    function listcontent_open() {
    }

    function listcontent_close() {}

    function code($aux, $state, $text) {
        // el primer arriba null (hauria de ser el text, però no ho es)
        // el segon es l'atribut {data-block-state="pre"}
        // el tercer es el text

        // TODO[Xavi] quan s'afegeixin els llenguatges del block de codi cal especificar-lo com atribut
        $this->doc .= "<code>" . $text . "</code>";
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

    function listu_open() {
        // Es reconeix correctament que es una llista desordenada
        return;
    }

    function listu_close() {}

    function listo_open($args) {
        // Es reconeix correctament que es una llista ordenada
        return;
    }

    function listo_close() {}

    function listitem_open($level) {
        // TODO[Xavi] No tenim el tipus de llista, s'ha de passar per paràmetre? com s'obté a l'original?
//         El nivell arriva correctament

        $indent = '';

        for ($i = 1; $i<$level; $i++){
            $indent .= '   ';
        }

        $this->doc .= "\n" . $indent . "-   ";
    }

    function listitem_close() {}

    function listcontent_open() {}

    function listcontent_close() {
        $this->doc .= "\n";
    }

    function code($text, $lang= null, $file = null) {
//        $this->doc .= '```' . $text . '```';
        $this->doc .= "~~~~\n" . $text . "\n~~~~";
    }
}

