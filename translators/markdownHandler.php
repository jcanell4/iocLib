<?php
/**
 * Description of MarkDownHandler
 *
 * @author josep
 */
class TranslatorHandler extends Doku_Handler{
    function __construct() {
        parent::Doku_Handler();
        $this->rewriteBlocks = FALSE;
    }
}

class MarkDown2DokuWikiHandler extends TranslatorHandler{

    function header($match, $state, $pos) {
        // get level and title
        $title = trim($match);
        $level = strspn($title,'#');
        if($level==0){
            $aTitle = split("\n", $title);
            $title = $aTitle[0];
            if($aTitle[1][0]=='='){
                $level = 1;
            }else{
                $level = 2;
            }
        }else{
            $title = trim($title,'#');
        }
        $title = trim($title);
        $title = preg_replace("/^([0-9]+.)+\s*/", "", $title, 1);
        $this->_addCall('header',array($title,$level,$pos), $pos); // nom de la funció, paràmetres que arribaran al render, punt d'analisis
        $this->_addCall('eol',array(),$pos); // final de linia

        return true;
    }


    function emphasis($match, $state, $pos)
    {
        switch ( $state ) {
//            case DOKU_LEXER_ENTER:
//                $this->_addCall($name.'_open', array(), $pos);
//                break;
//            case DOKU_LEXER_EXIT:
//                $this->_addCall($name.'_close', array(), $pos);
//                break;
            case DOKU_LEXER_UNMATCHED:
//                $this->_addCall('cdata',array($match), $pos);

                $title = '//' . trim($match). '//';

                $this->_addCall('emphasis',array($title), $pos);


                break;
        }

        return true;

//        $this->_nestingTag($match, $state, $pos, 'emphasis');


    }

//    function emphasis($match, $state, $pos) {
//        $title = '//' . trim($match). '//';
//        $this->_addCall('emphasis',array($title), $pos); // nom de la funció, paràmetres que arribaran al render, punt d'analisis
//
//        return true;
//    }
}

class DokuWiki2MarkDownHandler extends TranslatorHandler{
    protected $rootLevelValues;

    function __construct($rootLevelValues = array(1)) {
        parent::__construct();
        $this->rootLevelValues = $rootLevelValues;
    }

    function header($match, $state, $pos) {
        // get level and title
        $title = trim($match);
        $level = 7 - strspn($title,'=');
        if($level < 1) $level = 1;
        $title = trim($title,'=');
        $title = trim($title);

        $numLevels = $this->rootLevelValues;
        for($i= count($numLevels)-1; $i<$level; $i++){
            $numLevels[$i]=1;
        }

        $this->_addCall('header',array($title,$level,$numLevels, $pos), $pos);
        return true;
    }

    function emphasis($match, $state, $pos)
    {
        switch ( $state ) {
//            case DOKU_LEXER_ENTER:
//                $this->_addCall($name.'_open', array(), $pos);
//                break;
//            case DOKU_LEXER_EXIT:
//                $this->_addCall($name.'_close', array(), $pos);
//                break;
            case DOKU_LEXER_UNMATCHED:
//                $this->_addCall('cdata',array($match), $pos);

                $title = '*' . trim($match) . '*';

                $this->_addCall('emphasis',array($title), $pos);


                break;
        }

        return true;

//        $this->_nestingTag($match, $state, $pos, 'emphasis');


    }


//    function _nestingTag($match, $state, $pos, $name) {
//        switch ( $state ) {
//            case DOKU_LEXER_ENTER:
//                $this->_addCall($name.'_open', array(), $pos);
//                break;
//            case DOKU_LEXER_EXIT:
//                $this->_addCall($name.'_close', array(), $pos);
//                break;
//            case DOKU_LEXER_UNMATCHED:
//                $this->_addCall('cdata',array($match), $pos);
//                break;
//        }
//    }
}

