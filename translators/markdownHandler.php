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
            $aTitle = explode("\n", $title);
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
        $this->_addCall('header',array($title,$level,$pos), $pos);
        $this->_addCall('eol',array(),$pos);

        return true;
    }
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
}

