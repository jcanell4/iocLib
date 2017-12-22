<?php
class Doku_Parser_Mode_md2dw_header extends Doku_Parser_Mode {

    function connectTo($mode) {
        //we're not picky about the closing ones, two are enough
        $this->Lexer->addSpecialPattern(
                            '[ \t]*#{1,}[^\n]+[ \t]*(?=\n)|[^\n]+\n={2,}\n|[^\n]+\n-{2,}\n',
                            $mode,
                            'header'
                        );
    }

    function getSort() {
        return 50;
    }
}

class Doku_Parser_Mode_dw2md_header extends Doku_Parser_Mode {

    function connectTo($mode) {
        //we're not picky about the closing ones, two are enough
        $this->Lexer->addSpecialPattern(
                            '[ \t]*={2,}[^\n]+={2,}[ \t]*(?=\n)',
                            $mode,
                            'header'
                        );
    }

    function getSort() {
        return 50;
    }
}

class Doku_Parser_Mode_mddweol extends Doku_Parser_Mode {

    function connectTo($mode) {
        $badModes = array('listblock','table');
        if ( in_array($mode, $badModes) ) {
            return;
        }
        // see FS#1652, pattern extended to swallow preceding whitespace to avoid issues with lines that only contain whitespace
        $this->Lexer->addSpecialPattern('(?:^[ \t]*)?\n',$mode,'eol');
    }

    function getSort() {
        return 370;
    }
}


