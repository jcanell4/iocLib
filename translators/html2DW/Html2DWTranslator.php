<?php
// ALERTA[Xavi] Crec que això no fa falta, farem la conversió directament de HTML a DW


//class Html2DWTranslator extends AbstractMarkDownTranslator{
//    static function getParserModes(){
//        $ret = array(
//            // containers are complex modes that can contain many other modes
//            // hr breaks the principle but they shouldn't be used in tables / lists
//            // so they are put here
//            'container'    => array(/*'md2dw_listblock','md2dw_table','md2dw_quote','md2dw_hr'*/),
//
//            // some mode are allowed inside the base mode only
//            'baseonly'     => array('md2dw_header'),
//
//            // modes for styling text -- footnote behaves similar to styling
//            'formatting'   => array(/*'md2dw_strong', 'md2dw_emphasis', 'md2dw_underline', 'md2dw_monospace',
//                                    'md2dw_subscript', 'md2dw_superscript', 'md2dw_deleted', 'md2dw_footnote'*/),
//
//            // modes where the token is simply replaced - they can not contain any
//            // other modes
//            'substition'   => array(/*'md2dw_internallink','md2dw_media',
//                                    'md2dw_externallink','md2dw_linebreak','md2dw_emaillink',
//                                    'md2dw_windowssharelink','md2dw_filelink','md2dw_notoc',
//                                    'md2dw_nocache','md2dw_multiplyentity','md2dw_quotes','md2dw_rss'*/),
//
//            // modes which have a start and end token but inside which
//            // no other modes should be applied
//            'protected'    => array(/*'md2dw_preformatted','md2dw_code','md2dw_file','md2dw_php','md2dw_html','md2dw_htmlblock','md2dw_phpblock'*/),
//
//            // inside this mode no wiki markup should be applied but lineendings
//            // and whitespace isn't preserved
//            'disabled'     => array(/*'md2dw_unformatted'*/),
//
//            // used to mark paragraph boundaries
//            'paragraphs'   => array('mddweol')
//        );
//        return $ret;
//    }
//
//    function getStdModes(){
//        // add default modes
//        $std_modes = array(/*'md2dw_listblock','md2dw_preformatted','md2dw_notoc','md2dw_nocache',*/
//            'md2dw_header'/*,'md2dw_table','md2dw_linebreak','md2dw_footnote','md2dw_hr',
//                'md2dw_unformatted','md2dw_php','md2dw_html','md2dw_code','md2dw_file','md2dw_quote',
//                'md2dw_internallink','md2dw_rss','md2dw_media','md2dw_externallink',
//                'md2dw_emaillink','md2dw_windowssharelink'*/,'mddweol');
//        if(WikiGlobalConfig::getConf('typography')){
//            /*$std_modes[] = 'md2dw_quotes';
//            $std_modes[] = 'md2dw_multiplyentity';*/
//        }
//        return $std_modes;
//    }
//
//    function getFmtModes(){
//        $fmt_modes = array(/*'md2dw_strong','md2dw_emphasis','md2dw_underline','md2dw_monospace',
//                            'md2dw_subscript','md2dw_superscript','md2dw_deleted'*/);
//
//        return $fmt_modes;
//    }
//
//    function createInstanceHandler() {
//        require_once DOKU_INC . 'lib/lib_ioc/translators/markdownHandler.php';
//        return new MarkDown2DokuWikiHandler($this->rootLevelValues);
//    }
//
//    function createInstanceRender() {
//        require_once DOKU_INC . 'lib/lib_ioc/translators/markdownRenderer.php';
//        return new MarkDown2DokuWikiRender($this->rootLevelValues);
//    }
//
//    function getRenderFormat() {
//        return "html2dwtranslator";
//    }
//}