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



// ALERTA[Xavi] Proves

class Doku_Parser_Mode_dw2md_formatting extends Doku_Parser_Mode {
    var $type;

    var $formatting = array (
//        'strong' => array (
//            'entry'=>'\*\*(?=.*\*\*)',
//            'exit'=>'\*\*',
//            'sort'=>70
//        ),

        'emphasis'=> array (
            'entry'=>'//(?=.*//)',
            'exit'=>'//',
            'sort'=>80
        ),

//
        'underline'=> array (
            'entry'=>'__(?=.*__)',
            'exit'=>'__',
            'sort'=>90
        ),
//
        'monospace'=> array (
            'entry'=>"''(?=.*'')",
            'exit'=>"''",
            'sort'=>100
        ),
//
//        'subscript'=> array (
//            'entry'=>'<sub>(?=.*</sub>)',
//            'exit'=>'</sub>',
//            'sort'=>110
//        ),
//
//        'superscript'=> array (
//            'entry'=>'<sup>(?=.*</sup>)',
//            'exit'=>'</sup>',
//            'sort'=>120
//        ),
//
        'deleted'=> array (
            'entry'=>'<del>(?=.*</del>)',
            'exit'=>'</del>',
            'sort'=>130
        ),
    );

    function Doku_Parser_Mode_dw2md_formatting($type) {
        global $PARSER_MODES;

        $type = str_replace('dw2md_', '',$type);

        if ( !array_key_exists($type, $this->formatting) ) {
            trigger_error('Invalid formatting type '.$type, E_USER_WARNING);
        }

        $this->type = $type;

        // formatting may contain other formatting but not it self
        $modes = $PARSER_MODES['formatting'];
        $key = array_search($type, $modes);
        if ( is_int($key) ) {
            unset($modes[$key]);
        }

        $this->allowedModes = array_merge (
            $modes,
            $PARSER_MODES['substition'],
            $PARSER_MODES['disabled']
        );
    }

    function connectTo($mode) {

        // Can't nest formatting in itself
        if ( $mode == $this->type ) {
            return;
        }

        $this->Lexer->addEntryPattern(
            $this->formatting[$this->type]['entry'],
            $mode,
            $this->type
        );
    }

    function postConnect() {

        $this->Lexer->addExitPattern(
            $this->formatting[$this->type]['exit'],
            $this->type
        );

    }

    function getSort() {
        return $this->formatting[$this->type]['sort'];
    }
}

class Doku_Parser_Mode_md2dw_formatting extends Doku_Parser_Mode {
    var $type;

    var $formatting = array (
        'strong_emph' => array (
            'entry'=>'\*\*\*(?=.*\*\*\*)',
            'exit'=>'\*\*\*',
            'sort'=>60
        ),

        'strong' => array (
            'entry'=>'\*\*(?=.*\*\*)',
            'exit'=>'\*\*',
            'sort'=>70
        ),

        'emphasis'=> array (
            'entry'=>'\*(?=.*\*)',
            'exit'=>'\*',
            'sort'=>80
        ),

        'underline'=> array (
            'entry'=>'<ins>(?=.*</ins>)',
            'exit'=>'</ins>',
            'sort'=>90
        ),

        'monospace'=> array (
            'entry'=>'`(?=.*)',
            'exit'=>'`',
            'sort'=>100
        ),
//
//        'subscript'=> array (
//            'entry'=>'<sub>(?=.*</sub>)',
//            'exit'=>'</sub>',
//            'sort'=>110
//        ),
//
//        'superscript'=> array (
//            'entry'=>'<sup>(?=.*</sup>)',
//            'exit'=>'</sup>',
//            'sort'=>120
//        ),
//
        'deleted'=> array (
            'entry'=>'~~(?=.*~~)',
            'exit'=>'~~',
            'sort'=>130
        ),
    );

    function Doku_Parser_Mode_md2dw_formatting($type) {
        global $PARSER_MODES;

        $type = str_replace('md2dw_', '',$type);

        if ( !array_key_exists($type, $this->formatting) ) {
            trigger_error('Invalid formatting type '.$type, E_USER_WARNING);
        }

        $this->type = $type;

        // formatting may contain other formatting but not it self
        $modes = $PARSER_MODES['formatting'];
        $key = array_search($type, $modes);
        if ( is_int($key) ) {
            unset($modes[$key]);
        }

        $this->allowedModes = array_merge (
            $modes,
            $PARSER_MODES['substition'],
            $PARSER_MODES['disabled']
        );
    }

    function connectTo($mode) {

        // Can't nest formatting in itself
        if ( $mode == $this->type ) {
            return;
        }

        $this->Lexer->addEntryPattern(
            $this->formatting[$this->type]['entry'],
            $mode,
            $this->type
        );
    }

    function postConnect() {

        $this->Lexer->addExitPattern(
            $this->formatting[$this->type]['exit'],
            $this->type
        );

    }

    function getSort() {
        return $this->formatting[$this->type]['sort'];
    }
}


