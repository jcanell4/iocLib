<?php

if (!defined('DOKU_INC')) die();

/*
{
    PageStyle:{
        page-size: A3|A4|A5
        page-orientation: P|L    //(portrait|landscape)
        unit: pt|mm|cm|in    //(point|millimeter|centimeter|inch)
        page-magin-header: [0, +inf)
        page-magin-footer: [0, +inf)
        page-magin-top: [0, +inf)
        page-magin-bottom: [0, +inf)
        page-magin-left: [0, +inf)
        page-magin-right: [0, +inf)
        page-font-name:(font name list)
        page-font-size:[0, +inf)
    },
    ContainerStyle:{
        Style:{
            align: L|R|C|J    //(left|right|center|justify)
            padding: [0,+inf) | {top: [0,+inf), right: [0,+inf), bottom: [0,+inf), left: [0,+inf)}
            margin: [0,+inf) | {top: [0,+inf), right: [0,+inf), bottom: [0,+inf), left: [0,+inf)}
            border: TRUE|FALSE | {top: TRUE|FALSE, right: TRUE|FALSE, bottom: TRUE|FALSE, left: TRUE|FALSE}
            font-color: [#000000, #ffffff]
            font-name: (font name list)
            font-size: [0,+inf)    
            font-attribute: B|I|U|D|O       //(bold/italic/underline|line through|overline)
            background-color:[#000000, #ffffff]
            pos-x: [0,+inf)
            pos-y: [0,+inf)
            width: [0,+inf)
            height:  [0,+inf)
            replace-rule: map(of {type: (key list), keyToReplace: @@CONTENT@@|(symbol), textBase: (STRING contenint la clau keyToReplace)}  //exemple; {"type":"content",keyToReplace":"@@CONTENT@@","textBase":"<strong>@@CONTENT@@</strong>"})
        }
        childrenContainers: array(of maps(of keyString, ContainerStyle))
        styleTypes: array(of maps(of keyString, ContainerStyle)
    }
}
*/

class TcPdfStyle{
    const PAGE_ORIENTATION = "page-orientation";
    const PAGE_SIZE = "page-size";
    const UNIT = "unit";
    const PAGE_IMAGE_SCALE_RATIO = "page-image-scale-ratio";
    const PAGE_MARGIN_HEADER = "page-magin-header";
    const PAGE_MARGIN_FOOTER = "page-magin-footer";
    const PAGE_MARGIN_TOP = "page-magin-top";
    const PAGE_MARGIN_BOTTOM = "page-magin-bottom";
    const PAGE_MARGIN_LEFT = "page-magin-left";
    const PAGE_MARGIN_RIGHT = "page-magin-right";
    const PAGE_FONT_NAME = "page-font-name";
    const PAGE_FONT_SIZE = "page-font-size";
    const HEADER_LOGO_WIDTH = "header-logo-width";
    const HEADER_LOGO_HEIGHT = "header-logo-height";
    const HEADER_FONT_NAME = "header-font-name";
    const HEADER_FONT_SIZE = "header-font-size";
    const FOOTER_FONT_NAME = "footer-font-name";
    const FOOTER_FONT_SIZE = "footer-font-size";
    const MONOSPACE_FONT_NAME = "monospace-font-name";
    const FONT_NAME = "font-name";
    const FONT_SIZE = "font-size";
    const FONT_ATTR = "font-attribute";
    const PADDING = "padding";
    const PADDING_TOP = "padding-top";
    const PADDING_LEFT = "padding-left";
    const PADDING_RIGHT = "padding-right";
    const PADDING_BOTTOM = "padding-bottom";
    const MARGIN = "margin";
    const MARGIN_TOP = "margin-top";
    const MARGIN_LEFT = "margin-left";
    const MARGIN_RIGHT = "margin-right";
    const MARGIN_BOTTOM = "margin-bottom";
    const ALIGN = "align";
    const BORDER = "border";
    const BORDER_TOP = "border-top";
    const BORDER_LEFT = "border-left";
    const BORDER_RIGHT = "border-right";
    const BORDER_BOTTOM = "border-bottom";
    const BORDERCOLOR = "bordercolor";
    const BORDERCOLOR_TOP = "bordercolor-top";
    const BORDERCOLOR_LEFT = "bordercolor-left";
    const BORDERCOLOR_RIGHT = "bordercolor-right";
    const BORDERCOLOR_BOTTOM = "bordercolor-bottom";
    const BACKGROUND_COLOR = "background-color";
    const FONT_COLOR = "font-color";
    const POSITION = "position";
    const POSITION_X = "position-x";
    const POSITION_Y = "position-y";
    const IMAGE = "image";
    const IMAGE_FILENAMEMAP = "image-filenameMap";
    const IMAGE_BASEDIR = "image-basedir";
    const ICON_FILENAME = "icon-filepath";
    
    const PAGE_STYLE = "PageStyle";
    const CONTAINER_STYLE = "ContainerStyle";
    const STYLE = "style";
    const CHILDREN_CONTAINERS = "childrenContainers";
    const STYLE_TYPES = "styleTypes";
    const DEFAULT_CLASS_VALUE = "";
    const DEFAULT_STYLE_VALUES = [self::STYLE=>[]];
    const EMPTY_STYLE_VALUES = [self::STYLE=>[]];
    
    private $pageStyleDefs;
    private $styleStack;
    private $classStack;
    private $styleDepth;
    
    function __construct($styleStructure=FALSE) {
        if($styleStructure){
            $this->setStyleStructure($styleStructure);
        }else{
            $this->styleStack = array(self::DEFAULT_STYLE_VALUES);
            $this->classStack = array(self::DEFAULT_CLASS_VALUE);
            $this->styleDepth=0;            
        }
    }
    
    function setStyleStructure($styleStructure){
        $this->styleStack = array(self::DEFAULT_STYLE_VALUES);
        $this->classStack = array(self::DEFAULT_CLASS_VALUE);
        $this->styleDepth=0;
        $this->pageStyleDefs = $styleStructure[self::PAGE_STYLE];   
        $this->pushCurrentStyleContainer($styleStructure[self::CONTAINER_STYLE]);
    }
    
    function getPageStyleAttr($attr, $default=FALSE){
        if(isset($this->pageStyleDefs[$attr])){
            $ret = $this->pageStyleDefs[$attr];
        }else if(strpos($attr, '-')!==FALSE){
            $ret = $this->_getValueSplitingKey($this->pageStyleDefs, $attr);
        }
        if(!$ret){
            $ret = $default;
        }
        return $ret;
    }
    
    function getCurrentContainerStyleAttr($attr, $default=FALSE, $tryDefaultAsArray=TRUE){
        $ret = $this->_getSingleValueContainerStyleAttr($attr);
        if(!$ret && strpos($attr, "-")!==FALSE){
            $bound==0;
            do{
                $bound--;
                $aAttr = $this->_getSplitNameAttr($bound, $attr);
                $array = $this->_getSingleValueContainerStyleAttr($aAttr[0]);
                if($array){
                    if($trobat=isset($array[$aAttr[1]])){
                        $ret = $array[$aAttr[1]];
                    }else if(strpos($aAttr[1], "-")!==FALSE){
                        $ret = $this->_getValueSplitingKey($array, $aAttr[1]);
                    }
                }
            }while(!$trobat && $aAttr);          
        }
        if(!$ret){
            if($tryDefaultAsArray && is_array($default) && isset($default[$attr])){
                $ret = $default[$attr];
            }else{
                $ret = $default;
            }
        }
        return $ret;
    }
    
    function goInTextContainer($container, $class = self::DEFAULT_CLASS_VALUE){
        $containerObj = $this->_getSingleValueContainerStyleAttr($container, self::CHILDREN_CONTAINERS);
        if($containerObj){
            $this->pushCurrentStyleContainer($containerObj, $class);
        }else{
            $this->updateStyleClass($class);
             $this->pushCurrentStyleContainer(self::EMPTY_STYLE_VALUES);
        }
    }
    
    function goOutTextContainer(){
        $this->popCurrentStyleContainer();
    }
     
    private function _getValueSplitingKey($array, $attr){
        $ret = FALSE;
        $trobat = FALSE;
        $bound=0;
        do{
            $bound--;
            $aAttr = $this->_getSplitNameAttr($bound, $attr);
            if($aAttr && isset($array[$aAttr[0]])){
                if($trobat=isset($array[$aAttr[0]][$aAttr[1]])){
                    $ret = $array[$aAttr[0]][$aAttr[1]];
                }else if(strpos($aAttr[1], "-")!==FALSE){
                    $ret = $this->_getValue($array[$aAttr[0]], $aAttr[1]);
                }
            }
        }while(!$trobat && $aAttr);
        return $ret;
    }
    
    private function _getSplitNameAttr($bound, $attr){
        $ret = array("","");
        $aAttr = explode("-", $attr);
        $size= count($aAttr);
        if($bound<0){
            $bound = $size+$bound;
        }
        if($bound<=0){
            return FALSE;
        }
        for($i=0; $i<$bound; $i++){
            $ret[0] .= "-{$aAttr[$i]}";
        }
        for($i=$bound; $i<$size; $i++){
            $ret[1] .= "-{$aAttr[$i]}";
        }
        $ret[0] = substr($ret[0], 1);
        $ret[1] = substr($ret[1], 1);
        return $ret;        
    }
    
    private function _getSingleValueContainerStyleAttr($attr, $cat=self::STYLE){
        $ret = FALSE;
        $trobat=FALSE;
        $depth = $this->styleDepth;
        while($depth>=0 && !$trobat){
            if(isset($this->styleStack[$depth][$cat]) && ($trobat=isset($this->styleStack[$depth][$cat][$attr]))){
                $ret = $this->styleStack[$depth][$cat][$attr];
            }
            $depth--;
        }
        return $ret;    
    }
    
    private function getCurrentStyle(){
        return $this->styleStack[$this->styleDepth];
    }
    
    private function getCurrentClass(){
        return $this->classStack[$this->styleDepth];
    }
    
    private function pushCurrentStyleContainer($containerStyleStructure, $class = self::DEFAULT_CLASS_VALUE){
        $this->styleDepth++;
        $this->styleStack []= $containerStyleStructure;
        $this->classStack []= self::DEFAULT_CLASS_VALUE;
        $this->updateStyleClass($class);
    }
    
    private function popCurrentStyleContainer($popClassOnly=FALSE){
        $currentClass = $this->getCurrentClass();
        if($currentClass !== self::DEFAULT_CLASS_VALUE){
            array_pop($this->styleStack);
            array_pop($this->classStack);
            $this->styleDepth--;            
        }
        if(!$popClassOnly){
            array_pop($this->styleStack);
            array_pop($this->classStack);
            $this->styleDepth--;
        }
    }
    
    private function updateStyleClass($class){
        $currentClass = $this->getCurrentClass();
        if($currentClass!==self::DEFAULT_CLASS_VALUE){
            $this->popCurrentStyleContainer(TRUE);
        }
        if($class !== self::DEFAULT_CLASS_VALUE){
            $containerStyleStructure = $this->getCurrentStyle();
            if(isset($containerStyleStructure[self::STYLE_TYPES]) && isset($containerStyleStructure[self::STYLE_TYPES][$class])){
                $this->classStack []= $class;
                $this->styleStack []= $containerStyleStructure[self::STYLE_TYPES][$class];
                $this->styleDepth++;
            }
        }
    }
}

require_once DOKU_INC."inc/inc_ioc/tcpdf/tcpdf_include.php";
require_once DOKU_INC."inc/inc_ioc/tcpdf/tcpdf.php";

class BasicIocTcPdf extends TCPDF{
    private $counterToSaveTmpPdf = 0;
    private $nexStyletAttributes = array();
    private $defaultMargins;
    
    protected $head; 
    protected $style;
    protected $header_logo_height;

    public function __construct(TcPdfStyle &$style) {
        //"$orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding='UTF-8', $diskcache=false, $pdfa=false"
        parent::__construct($style->getPageStyleAttr(TcPdfStyle::PAGE_ORIENTATION, "P"), 
                            $style->getPageStyleAttr(TcPdfStyle::UNIT, "mm"), 
                            $style->getPageStyleAttr(TcPdfStyle::PAGE_SIZE, "A4"), 
                            TRUE, "UTF-8", FALSE, FALSE);
        $this->style = $style;
        $this->header_logo_width = $style->getPageStyleAttr(TcPdfStyle::HEADER_LOGO_WIDTH, 8);
        $this->SetMargins($style->getPageStyleAttr(TcPdfStyle::PAGE_MARGIN_LEFT, PDF_MARGIN_LEFT), 
                            $style->getPageStyleAttr(TcPdfStyle::PAGE_MARGIN_TOP, PDF_MARGIN_TOP), 
                            $style->getPageStyleAttr(TcPdfStyle::PAGE_MARGIN_RIGHT, PDF_MARGIN_RIGHT));
        $this->header_logo_height = $style->getPageStyleAttr(TcPdfStyle::HEADER_LOGO_HEIGHT, 10);
        //$this->header_font = $style->getPageStyleAttr(TcpPdfStyle::HEADER_FONT_NAME, "helvetica");
        $this->SetHeaderMargin($style->getPageStyleAttr(TcPdfStyle::PAGE_MARGIN_HEADER, PDF_MARGIN_HEADER));
        $this->SetFooterMargin($style->getPageStyleAttr(TcPdfStyle::PAGE_MARGIN_FOOTER, PDF_MARGIN_FOOTER));
        
        $this->SetDefaultMonospacedFont($style->getPageStyleAttr(TcPdfStyle::MONOSPACE_FONT_NAME, "Courier"));
        $this->SetAutoPageBreak(TRUE, $style->getPageStyleAttr(TcPdfStyle::PAGE_MARGIN_BOTTOM, PDF_MARGIN_BOTTOM));        
        $this->setImageScale($style->getPageStyleAttr(TcPdfStyle::PAGE_IMAGE_SCALE_RATIO, PDF_IMAGE_SCALE_RATIO));
        $this->defaultMargins = array_merge(array(), $this->getMargins());
    }

    public function popNextAttributes(){
        $ret = array_merge(array(),$this->nexStyletAttributes);
        $this->nexStyletAttributes = array();
        return $ret;
    }
    
    public function setBorderFromCurrentStyle(){
        $allBordeStyr = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDER);
        $border = "";
        if($this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDER_LEFT, $allBordeStyr)){
            $border.="L";
        }
        if($this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDER_TOP, $allBordeStyr)){
            $border.="T";
        }
        if($this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDER_RIGHT, $allBordeStyr)){
            $border.="R";
        }
        if($this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDER_BOTTOM, $allBordeStyr)){
            $border.="B";
        }
        if(!empty($border)){
            $this->nexStyletAttributes[TcPdfStyle::BORDER] = $border;
            $bcolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR, FALSE);
            if($bcolor){
                $res = array();
                $this->nexStyletAttributes[TcPdfStyle::BORDERCOLOR] = TCPDF_COLORS::convertHTMLColorToDec($bcolor, $res);
            }
        }
    }
    
    public function setIconContainerToRender($iconName){
        $iconFile = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::ICON_FILENAME, FALSE);
        if(!$iconFile){
            $miconFile = $this->style->getPageStyleAttr(TcPdfStyle::IMAGE_FILENAMEMAP, FALSE);
            $iconFile = $miconFile?$miconFile[$iconName]:FALSE;
        }
        if($iconFile){
            $baseDir="";
            if($iconFile["needBaseDir"]){
                $baseDir = $this->style->getPageStyleAttr(TcPdfStyle::IMAGE_BASEDIR, "");
            }
            $iconPath = DOKU_INC."$baseDir{$iconFile["filepath"]}";
            $this->nexStyletAttributes["renderIconContainer"] = $iconPath;
        }
    }
    
    public function setFillColorFromCurrentStyle(){
        $bcolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BACKGROUND_COLOR, FALSE);
        if($bcolor){
            $res = array();
            $this->nexStyletAttributes[TcPdfStyle::BACKGROUND_COLOR] = TCPDF_COLORS::convertHTMLColorToDec($bcolor, $res);
        }
    }
    
    public function setPositonFromCurrentStyle($x=FALSE, $y=FALSE){
        $xy = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::POSITION, FALSE);
        $x = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::POSITION_X, $xy);
        $y = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::POSITION_Y, $xy);
        if($x){
            $this->nexStyletAttributes[TcPdfStyle::POSITION_X] = $x;
        }
        if($y){
            $this->nexStyletAttributes[TcPdfStyle::POSITION_Y] = $y;
        }
    }
    
    public function setFontFromCurrentStyle($fontNameDef="helvetica", $fontAttrDef="", $fontSizeDef=10){
        $fontName = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::FONT_NAME, $fontNameDef);
        $fontAttr = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::FONT_ATTR, $fontAttrDef);
        $fontSize = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::FONT_SIZE, $fontSizeDef);
        $this->SetFont($fontName, $fontAttr, $fontSize);        
    }
    
    public function setCellPaddingsFromCurrentStyle($left = '', $top = '', $right = '', $bottom = '') {
        $margins = $this->defaultMargins;
        $padding = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::PADDING, 0);
        if(is_array($padding)){
            $left = isset($padding["left"])?$padding["left"]:$margins["padding_left"];
            $top = isset($padding["top"])?$padding["top"]:$margins["padding_top"];
            $right = isset($padding["right"])?$padding["right"]:$margins["padding_right"];
            $bottom = isset($padding["bottom"])?$padding["bottom"]:$margins["padding_bottom"];
        }else if($padding!==0){
            $left=$top=$bottom=$right=$padding;
        }else{
            if(empty($left)){
                $left = $margins["padding_left"];
            }
            if(empty($top)){
                $top = $margins["padding_top"];
            }
            if(empty($right)){
                $right = $margins["padding_right"];
            }
            if(empty($bottom)){
                $bottom = $margins["padding_bottom"];
            }            
        }
        $left = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::PADDING_LEFT, $left);
        $top = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::PADDING_TOP, $top);
        $right = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::PADDING_RIGHT, $right);
        $bottom = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::PADDING_BOTTOM, $bottom);
        parent::setCellPaddings($left, $top, $right, $bottom);
    }
    
    public function setCellMarginsFromCurrentStyle($left = 0, $top = 0, $right = 0, $bottom = 0) {
        $margin = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::MARGIN, 0);
        if(is_array($margin)){
            $left = isset($margin["left"])?$margin["left"]:$left;
            $top = isset($margin["top"])?$margin["top"]:$top;
            $right = isset($margin["right"])?$margin["right"]:$right;
            $bottom = isset($margin["bottom"])?$margin["bottom"]:$bottom;
        }else if($margin!==0){
            $left=$top=$bottom=$right=$margin;
        }
        $left = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::MARGIN_LEFT, $left);
        $top = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::MARGIN_TOP, $top);
        $right = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::MARGIN_RIGHT, $right);
        $bottom = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::MARGIN_BOTTOM, $bottom);
        parent::setCellMargins($left, $top, $right, $bottom);
    }
    
    function saveTmpPdfAsString(){
        $npage = 1;
        $content = "";
        foreach ($this->pages as $page){
            $content .= $page."\n\npage:".$npage."\n\n\n";
            $npage++;
        }
        @file_put_contents(DOKU_INC."lib/plugins/tmp/tmpdoc{$this->counterToSaveTmpPdf}.txt", $content);
        $this->counterToSaveTmpPdf++;      
    }
    
    public function writeHTMLCell($w, $h, $x, $y, $html='', $border=0, $ln=0, $fill=false, $reseth=true, $align='', $autopadding=true) {
        $prev_cell_margin = $this->cell_margin;
        $prev_cell_padding = $this->cell_padding;
        // adjust internal padding
        $this->adjustCellPadding($border);
        $extraHeight = $this->getCellHeight($this->getFontSize(), false);
        $mc_margin = $this->cell_margin;
        $mc_padding = $this->cell_padding;
        $this->setCellPaddings($mc_padding['L'], $mc_padding['T'], 0, $mc_padding['B']);
        $startpage = $this->getPage();
        $startcolumn = $this->current_column;
        $oy = (!TCPDF_STATIC::empty_string($y)?$y:$this->GetY())+$mc_margin["T"];
        $ox = (!TCPDF_STATIC::empty_string($x)?$x: $this->GetX());
        $w=$w-$mc_padding["R"];

//        $ret = $this->MultiCell($w, $h, $html, $border, $align, $fill, $ln, $x, $y, $reseth, 4, true, $autopadding, 0, 'T', false);        
        $ret = $this->MultiCell($w, $h, $html, 0, $align, false, $ln, $x, $y, $reseth, 4, true, $autopadding, 0, 'T', false);        
        
        $this->setCellPaddings(0, 0, 0, 0);
        $this->setCellMargins(0, 0, 0, 0);

        if($border || $fill){
//            $this->SetFillColor(0, 128, 0);
            $w += $mc_padding["R"];
            $endpage = $this->getPage();
            $endcolumn = $this->current_column;
            $currentY = $this->GetY();
            $endY = $this->GetY()-$extraHeight-$extraHeight-$mc_margin["B"]+1;
            $currentx = $this->GetX();
            // design borders around HTML cells.
            for ($page = $startpage; $page <= $endpage; ++$page) { // for each page
                $ccode = '';
                $this->setPage($page);
                if ($this->num_columns < 2) {
                        // single-column mode
                        $this->SetX($ox);
                        $this->y = $this->tMargin;
                }
                // account for margin changes
                if ($page > $startpage) {
                        if (($this->rtl) AND ($this->pagedim[$page]['orm'] != $this->pagedim[$startpage]['orm'])) {
                                $this->x -= ($this->pagedim[$page]['orm'] - $this->pagedim[$startpage]['orm']);
                        } elseif ((!$this->rtl) AND ($this->pagedim[$page]['olm'] != $this->pagedim[$startpage]['olm'])) {
                                $this->x += ($this->pagedim[$page]['olm'] - $this->pagedim[$startpage]['olm']);
                        }
                }
                if ($startpage == $endpage) {
                        // single page
                        for ($column = $startcolumn; $column <= $endcolumn; ++$column) { // for each column
                                if ($column != $this->current_column) {
                                        $this->selectColumn($column);
                                }
                                if ($this->rtl) {
                                        $this->x -= $mc_margin['R'];
                                } else {
                                        $this->x += $mc_margin['L'];
                                }
                                if ($startcolumn == $endcolumn) { // single column
                                        $cborder = $border;
                                        $h = max($h, ($endY - $oy));
                                        $this->y = $oy;
                                } elseif ($column == $startcolumn) { // first column
                                        $cborder = $border_start;
                                        $this->y = $oy;
                                        $h = $this->h - $this->y - $this->bMargin;
                                } elseif ($column == $endcolumn) { // end column
                                        $cborder = $border_end;
                                        $h = $endY - $this->y;
                                        if ($resth > $h) {
                                                $h = $resth;
                                        }
                                } else { // middle column
                                        $cborder = $border_middle;
                                        $h = $this->h - $this->y - $this->bMargin;
                                        $resth -= $h;
                                }
                                $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true)."\n";
                        } // end for each column
                } elseif ($page == $startpage) { // first page
                        for ($column = $startcolumn; $column < $this->num_columns; ++$column) { // for each column
                                if ($column != $this->current_column) {
                                        $this->selectColumn($column);
                                }
                                if ($this->rtl) {
                                        $this->x -= $mc_margin['R'];
                                } else {
                                        $this->x += $mc_margin['L'];
                                }
                                if ($column == $startcolumn) { // first column
                                        $cborder = $border_start;
                                        $this->y = $oy;
                                        $h = $this->h - $this->y - $this->bMargin;
                                } else { // middle column
                                        $cborder = $border_middle;
                                        $h = $this->h - $this->y - $this->bMargin;
                                        $resth -= $h;
                                }
                                $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true)."\n";
                        } // end for each column
                } elseif ($page == $endpage) { // last page
                        for ($column = 0; $column <= $endcolumn; ++$column) { // for each column
                                if ($column != $this->current_column) {
                                        $this->selectColumn($column);
                                }
                                if ($this->rtl) {
                                        $this->x -= $mc_margin['R'];
                                } else {
                                        $this->x += $mc_margin['L'];
                                }
                                if ($column == $endcolumn) {
                                        // end column
                                        $cborder = $border_end;
                                        $h = $endY - $this->y;
                                        if ($resth > $h) {
                                                $h = $resth;
                                        }
                                } else {
                                        // middle column
                                        $cborder = $border_middle;
                                        $h = $this->h - $this->y - $this->bMargin;
                                        $resth -= $h;
                                }
                                $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true)."\n";
                        } // end for each column
                } else { // middle page
                        for ($column = 0; $column < $this->num_columns; ++$column) { // for each column
                                $this->selectColumn($column);
                                if ($this->rtl) {
                                        $this->x -= $mc_margin['R'];
                                } else {
                                        $this->x += $mc_margin['L'];
                                }
                                $cborder = $border_middle;
                                $h = $this->h - $this->y - $this->bMargin;
                                $resth -= $h;
                                $ccode .= $this->getCellCode($w, $h, '', $cborder, 1, '', $fill, '', 0, true)."\n";
                        } // end for each column
                }
                if ($cborder OR $fill) {
                        $offsetlen = strlen($ccode);
                        // draw border and fill
                        if ($this->inxobj) {
                                // we are inside an XObject template
                                if (end($this->xobjects[$this->xobjid]['transfmrk']) !== false) {
                                        $pagemarkkey = key($this->xobjects[$this->xobjid]['transfmrk']);
                                        $pagemark = $this->xobjects[$this->xobjid]['transfmrk'][$pagemarkkey];
                                        $this->xobjects[$this->xobjid]['transfmrk'][$pagemarkkey] += $offsetlen;
                                } else {
                                        $pagemark = $this->xobjects[$this->xobjid]['intmrk'];
                                        $this->xobjects[$this->xobjid]['intmrk'] += $offsetlen;
                                }
                                $pagebuff = $this->xobjects[$this->xobjid]['outdata'];
                                $pstart = substr($pagebuff, 0, $pagemark);
                                $pend = substr($pagebuff, $pagemark);
                                $this->xobjects[$this->xobjid]['outdata'] = $pstart.$ccode.$pend;
                        } else {
                                if (end($this->transfmrk[$this->page]) !== false) {
                                        $pagemarkkey = key($this->transfmrk[$this->page]);
                                        $pagemark = $this->transfmrk[$this->page][$pagemarkkey];
                                        $this->transfmrk[$this->page][$pagemarkkey] += $offsetlen;
                                } elseif ($this->InFooter) {
                                        $pagemark = $this->footerpos[$this->page];
                                        $this->footerpos[$this->page] += $offsetlen;
                                } else {
                                        $pagemark = $this->intmrk[$this->page];
                                        $this->intmrk[$this->page] += $offsetlen;
                                }
                                $pagebuff = $this->getPageBuffer($this->page);
                                $pstart = substr($pagebuff, 0, $pagemark);
                                $pend = substr($pagebuff, $pagemark);
                                $this->setPageBuffer($this->page, $pstart.$ccode.$pend);
                        }
                }
            } // end for each page

            $this->SetY($currentY);
            $this->SetX($currentx);
        }
        
        return $ret;
    }
    
}

class BasicPdfRenderer {
    protected $style;
    protected $tableCounter = 0;
    protected $tableReferences = array();
    protected $tablewidths = array();
    protected $nColInRow = 0;
    protected $figureCounter = 0;
    protected $figureReferences = array();
    protected $headerNum = array(0,0,0,0,0,0);
    protected $headerFont = "helvetica";
    protected $headerFontSize = 10;
    protected $footerFont = "helvetica";
    protected $footerFontSize = 8;
    protected $firstPageFont = "Times";
    protected $pagesFont = "helvetica";
    protected $pagesFontHt = "Times";
    protected $pagesFontSize = 10;
    protected $pagesFontHtSize = 12;
    protected $state = ["table" => ["type" => "table"]];
    protected $tcpdfObj = NULL;
    protected $maxImgSize = 100;
    protected $iocTcPdf;

    public function __construct() {
        $this->maxImgSize = WikiGlobalConfig::getConf('max_img_size', 'wikiiocmodel');
    }

    public function resetDataRender() {
        $this->tableCounter = 0;
        $this->tableReferences = array();
        $this->tablewidths = array();
        $this->nColInRow = 0;
        $this->figureCounter = 0;
        $this->figureReferences = array();
        $this->headerNum = array(0,0,0,0,0,0);
        $this->headerFont = "helvetica";
        $this->headerFontSize = 10;
        $this->footerFont = "helvetica";
        $this->footerFontSize = 8;
        $this->firstPageFont = "Times";
        $this->pagesFont = "helvetica";
        $this->pagesFontHt = "Times";
        $this->pagesFontHtSize=12;
        $this->pagesFontSize=10;
        $this->state = ["table" => ["type" => "table"]];
    }
    
    public function renderToc(){
        $this->iocTcPdf->setFontFromCurrentStyle($this->pagesFont, "", $this->pagesFontSize);
        $this->iocTcPdf->setCellPaddingsFromCurrentStyle();
        $this->iocTcPdf->setCellMarginsFromCurrentStyle();
        $this->iocTcPdf->setBorderFromCurrentStyle();
        $this->iocTcPdf->setFillColorFromCurrentStyle();  
        $this->iocTcPdf->setPositonFromCurrentStyle();

        $this->iocTcPdf->addTOCPage();

        // write the TOC title
        $this->iocTcPdf->SetFont($this->pagesFontHt, 'B', 16);
        $this->iocTcPdf->MultiCell(0, 0, 'Índex', 0, 'C', 0, 1, '', '', true, 0);
        $this->iocTcPdf->Ln();

        // add a simple Table Of Content at first page
        $this->iocTcPdf->SetFont($this->pagesFontHt, '', $this->pagesFontHtSize);
        $this->iocTcPdf->addTOC(2, 'courier', '.', 'INDEX', 'B', array(128,0,0));

        // end of TOC page
        $this->iocTcPdf->endTOCPage();
        
    }

    public function renderDocument($params, $output_filename="") {
        if (empty($output_filename)) {
            $output_filename = str_replace(":", "_", $params["id"]);
        }
        if(isset($params["style"])){
            $this->style = new TcPdfStyle($this->getPdfStyleFromFile($params["style"]));
        }else{
            $this->style = new TcPdfStyle();
        }


        $this->iocTcPdf = new IocTcPdf($this->style);
        $this->iocTcPdf->SetCreator("DOKUWIKI IOC");
        $this->iocTcPdf->setHeaderData($params["data"]["header"]["logo"], $params["data"]["header"]["wlogo"], $params["data"]["header"]["hlogo"], $params["data"]["header"]["ltext"], $params["data"]["header"]["rtext"]);
        $this->setMaxImgSize($params['max_img_size']);

        // set header and footer fonts
        $this->iocTcPdf->setHeaderFont(Array($this->style->getPageStyleAttr(TcPdfStyle::HEADER_FONT_NAME, $this->headerFont), '', 
                                                $this->style->getPageStyleAttr(TcPdfStyle::HEADER_FONT_SIZE, $this->headerFontSize)));
        $this->iocTcPdf->setFooterFont(Array($this->style->getPageStyleAttr(TcPdfStyle::FOOTER_FONT_NAME, $this->footerFont), '', 
                                                $this->style->getPageStyleAttr(TcPdfStyle::FOOTER_FONT_SIZE, $this->footerFontSize)));
        $this->pagesFont = $this->style->getPageStyleAttr(TcPdfStyle::PAGE_FONT_NAME, "helvetica");
    }
    
    protected function setMaxImgSize($max_img_size) {
        $this->maxImgSize = $max_img_size;
    }

    protected function getMaxImgSize() {
        return $this->maxImgSize;
    }

    protected function resolveReferences($content) {
        if (!empty($content["id"])) {
            if ($content["type"]===TableFrame::TABLEFRAME_TYPE_TABLE || $content["type"]===TableFrame::TABLEFRAME_TYPE_ACCOUNTING) {
                $this->tableCounter++;
                $this->tableReferences[$content["id"]] = $this->tableCounter;
            }elseif ($content["type"]===FigureFrame::FRAME_TYPE_FIGURE) {
                $this->figureCounter++;
                $this->figureReferences[$content["id"]] = $this->figureCounter;
            }
        }
        if (!empty($content["content"])) {
            for ($i=0; $i<count($content["content"]); $i++) {
                $this->resolveReferences($content["content"][$i]);
            }
        }
        if (!empty($content["children"])) {
            for ($i=0; $i<count($content["children"]); $i++) {
                $this->resolveReferences($content["children"][$i]);
            }
        }
    }

    protected function renderHeader($header, IocTcPdf &$iocTcPdf) {
        if ($header['type'] !== StructuredNodeDoc::ROOTCONTENT_TYPE) {
            $this->style->goInTextContainer($header["type"]);
            $level = $header["level"]-1;
            $this->style->goInTextContainer($header["type"].$header["level"]);
            $iocTcPdf->setFontFromCurrentStyle($this->pagesFontHt, "B", $this->pagesFontHtSize);
            $this->iocTcPdf->setCellPaddingsFromCurrentStyle();
            $this->iocTcPdf->setCellMarginsFromCurrentStyle();
            $this->iocTcPdf->setBorderFromCurrentStyle();
            $this->iocTcPdf->setFillColorFromCurrentStyle();  
            $this->iocTcPdf->setPositonFromCurrentStyle();
            $title = $this->incHeaderCounter($level).$header["title"];

            //Control de espacio disponible para impedir títulos huérfanos
            if ($iocTcPdf->GetY() + 40 > $iocTcPdf->getPageHeight()) {
                $iocTcPdf->AddPage(); //inserta salto de pagina
            }

            $iocTcPdf->Bookmark($title, $level, 0);
            $iocTcPdf->Ln(5);
            $iocTcPdf->Cell(0, 0, $title, 0,1, "L");
            $iocTcPdf->Ln(3);
            $this->style->goOutTextContainer();
            $this->style->goOutTextContainer();
        }

        if (!empty($header["content"])) {
            for ($i=0; $i<count($header["content"]); $i++) {
                $this->renderContent($header["content"][$i], $iocTcPdf);
            }
        }
        if (!empty($header["children"])) {
            for ($i=0; $i<count($header["children"]); $i++) {
                $this->renderHeader($header["children"][$i], $iocTcPdf);
            }
        }
    }

    protected function getHeaderCounter($level) {
        $ret = "";
        for ($i=0; $i<=$level; $i++) {
            $ret .= $this->headerNum[$i].".";
        }
        return $ret." ";
    }

    protected function incHeaderCounter($level) {
        $this->headerNum[$level]++;
        for ($i=$level+1; $i<count($this->headerNum); $i++) {
            $this->headerNum[$i]=0;
        }
        return $this->getHeaderCounter($level);
    }

    protected function renderContent($content, IocTcPdf &$iocTcPdf, $pre="", $post="") {
//        $iocTcPdf->SetFont('helvetica', '', 10);
        if ($content['type'] === FigureFrame::FRAME_TYPE_FIGURE) {
            $this->getFrameContent($content, $iocTcPdf);
        }else {
            $ret = $this->getContent($content);
            $this->_cleanWriteHTML($ret, $iocTcPdf);
        }

        if ($content["type"] == StructuredNodeDoc::ORDERED_LIST_TYPE
                || $content["type"] == StructuredNodeDoc::UNORDERED_LIST_TYPE
                || $content["type"] == StructuredNodeDoc::PARAGRAPH_TYPE) {
            $iocTcPdf->Ln(3);
        }
    }

    protected function getFrameContent($content, IocTcPdf &$iocTcPdf) {
        switch ($content['type']) {
            case ImageNodeDoc::IMAGE_TYPE:
                $this->renderImage($content, $iocTcPdf);
                break;

            case FigureFrame::FRAME_TYPE_FIGURE:
                // Comprueba si queda suficiente espacio vertical para poner la imagen
                // junto al título, es decir, si cabe el bloque título + imagen en el resto de página
                list($w, $h) = $this->setImageSize($content['content'][0]['content'][0]['src'], $content['content'][0]['content'][0]['width'], $content['content'][0]['content'][0]['height']);
                if ($iocTcPdf->GetY() + $h + 25 > $iocTcPdf->getPageHeight()) {
                    $iocTcPdf->AddPage(); //inserta salto de pagina
                }
                $center = "style=\"margin:auto; text-align:center;";
                if ($content["hasBorder"]) {
                    $style = $center . " border:1px solid gray;";
                }
                $ret = "<div $style nobr=\"true\">";
                if ($content['title']) {
                    $ret .= "<p $center font-weight:bold;\">Figura ".$this->figureReferences[$content['id']].". ".$content['title']."</p>";
                }
                $this->_cleanWriteHTML($ret, $iocTcPdf);
                $ret = $this->getFrameStructuredContent($content, $iocTcPdf);
                if ($content['footer']) {
                    if ($content['title']) {
                        $ret .= "<p $center font-size:80%;\">".$content['footer']."</p>";
                    }else {
                        $ret .= "<p $center font-size:80%;\">Figura ".$this->figureReferences[$content['id']].". ".$content['footer']."</p>";
                    }
                }
                $ret .= "</div>";
                $this->_cleanWriteHTML($ret, $iocTcPdf);
                break;

            default:
                $this->getFrameStructuredContent($content, $iocTcPdf);
                break;
        }
        return "";
    }

    protected function getFrameStructuredContent($content, IocTcPdf &$iocTcPdf) {
        $ret = "";
        $limit = count($content['content']);
        for ($i=0; $i<$limit; $i++) {
            $ret .= $this->getFrameContent($content['content'][$i], $iocTcPdf);
        }
        return $ret;
    }

    /**
     * Neteja de caracters indesitjables el text que s'envia a ser codificat com a PDF
     * @param $content (string) text to convert
     * @param $iocTcPdf (IocTcPdf)
     * @param $ln (boolean) if true add a new line after text (default = true)
     * @param $fill (boolean) Indicates if the background must be painted (true) or transparent (false).
     * @param $reseth (boolean) if true reset the last cell height (default false).
     * @param $cell (boolean) if true add the current left (or right for RTL) padding to each Write (default false).
     * @param $align (string) Allows to center or align the text. Possible values are: L:left align - C:center - R:right align - empty:left for LTR or right for RTL
     */
    private function _cleanWriteHTML($content, IocTcPdf &$iocTcPdf) {
        $c = 0;
        $aSearch = ["/0xFF/", "/0xFEFF/"];
        $aReplace = [" ", " "];
        $content = preg_replace($aSearch, $aReplace, $content, -1, $c);
        if ($c > 0) {
            $content = str_replace($aSearch, $aReplace, $content);
        }
//        $iocTcPdf->writeHTML($content, $ln, $fill, $reseth, $cell, $align);
        $margins = $iocTcPdf->getMargins();
        $cellMargins = $iocTcPdf->getCellMargins();
        $cellPaddings = $iocTcPdf->getCellPaddings();
        $lineheight = $iocTcPdf->getCellHeight($iocTcPdf->getFontSize(), FALSE);
        
        if(($iocTcPdf->getY()+$cellMargins["T"]+$cellMargins["B"]+$cellPaddings["T"]+$cellPaddings["B"]+$lineheight+$lineheight+$margins["bottom"])>=$iocTcPdf->getPageHeight()){
            $iocTcPdf->AddPage();
        }
        
        $w = $iocTcPdf->getPageWidth()-$cellMargins["L"]-$cellMargins["R"]-$margins["left"]-$margins["right"];
        $y = $iocTcPdf->getY();
        
        $nextAttributes = $iocTcPdf->popNextAttributes();
        
        $x = $nextAttributes[TcPdfStyle::POSITION_X];
        if(is_numeric($x)){
            if($x<0){
                $maxw = abs($x);
                $x = $iocTcPdf->getPageWidth()+$x;
                $w = $maxw-$cellMargins["L"]-$cellMargins["R"]-$margins["right"];
            }
        }else{
            $x="";
        }        
        
        if(isset($nextAttributes[TcPdfStyle::BORDER])){
            $border = $nextAttributes[TcPdfStyle::BORDER];
        }else{
            $border=0;
        }        
        if(isset($nextAttributes[TcPdfStyle::BACKGROUND_COLOR])){
            $fill = TRUE;
            $iocTcPdf->SetFillColor($nextAttributes[TcPdfStyle::BACKGROUND_COLOR]["R"], $nextAttributes[TcPdfStyle::BACKGROUND_COLOR]["G"], $nextAttributes[TcPdfStyle::BACKGROUND_COLOR]["B"]);
        }else{
            $fill = FALSE;
        }
        
        $numPage = $iocTcPdf->getPage();
        
        $iocTcPdf->writeHTMLCell($w, "", $x, $y, $content, $border, 1, $fill, true, "", true);
        if(isset($nextAttributes["renderIconContainer"])){
            $this->renderIconIocContainer($x, $y, 0, $numPage, $nextAttributes["renderIconContainer"], $iocTcPdf);
        }
//        $iocTcPdf->saveTmpPdfAsString();
    }

    private function renderSmiley($content, IocTcPdf &$iocTcPdf) {
        preg_match('/\.(.+)$/', $content['src'], $match);
        $ext = ($match) ? $match[1] : "JPG";
        $iocTcPdf->Image($content['src'], '', '', 0, 0, $ext, '', 'T');
    }

    protected function renderIconIocContainer($x, $y, $width=32, $numPage, $iconPath, IocTcPdf &$iocTcPdf) {
        preg_match('/\.(.+)$/', $iconPath, $match);
        $ext = ($match) ? $match[1] : "JPG";
        //càlcul de les dimensions de la imatge
        if($width==0){
            $width = 32;
        }
        list($w, $h) = $this->setImageSize($iconPath, NULL, NULL, $width, $width);
        $currentY = $iocTcPdf->GetY();
        $currentX = $iocTcPdf->GetX();
        $currentPage = $iocTcPdf->getPage();
        if($currentPage!==$numPage){
            $iocTcPdf->setPage($numPage);
        }
        $iocTcPdf->SetY($y);
        if ($y + $h > $iocTcPdf->getPageHeight()) {
            $iocTcPdf->AddPage(); //inserta salto de pagina
        }
        //inserció de la imatge
       //$iocTcPdf::Image(file, x, y, width, height, type/extension, link, valign, resize, dpi, halign, ismask, imgmask, border, fitbox, hidden, fitonpage, alt, altimgs);
        $iocTcPdf->Image($iconPath, $x, $y, $w, 0, $ext, '', 'T', true, 300, 'R');
        $iocTcPdf->setPage($currentPage);
        $iocTcPdf->SetY($currentY); 
        $iocTcPdf->SetX($currentX);
    }
    
    protected function renderImage($content, IocTcPdf &$iocTcPdf) {
        preg_match('/\.(.+)$/', $content['src'], $match);
        $ext = ($match) ? $match[1] : "JPG";
        //càlcul de les dimensions de la imatge
        list($w, $h) = $this->setImageSize($content['src'], $content['width'], $content['height']);
        if ($iocTcPdf->GetY() + $h > $iocTcPdf->getPageHeight()) {
            $iocTcPdf->AddPage(); //inserta salto de pagina
        }
        //inserció de la imatge
       //$iocTcPdf::Image(file, x, y, width, height, type/extension, link, valign, resize, dpi, halign, ismask, imgmask, border, fitbox, hidden, fitonpage, alt, altimgs);
        $iocTcPdf->Image($content['src'], '', '', $w, 0, $ext, '', 'T', true, 300, 'C');
        $iocTcPdf->SetY($iocTcPdf->GetY() + $h); //correcció de la coordinada Y desprès de insertar la imatge
        //inserció del títol a sota de la imatge
        $center = "style=\"margin:auto; text-align:center;";
        $text = "<p $center font-size:80%;\">{$content['title']}</p>";
        $this->_cleanWriteHTML($text, $iocTcPdf);
    }

    private function setImageSize($imageFile, $w=NULL, $h=NULL, $maxWidth=800, $maxHeight=1200) {
        if (@file($imageFile)) {
            list($w0, $h0) = getimagesize($imageFile);
        }
        if ($w0 == NULL) {
            $w0 = $h0 = 5;
        }

        if ($w==NULL) {
            if ($w0 <= $maxWidth) {
                $w = $w0;
            }else {
                $factor_reduc = $maxWidth / $w0;
                $w = $maxWidth;
            }
        }else {
            $factor_reduc = $w / $w0;
        }
        if ($h==NULL) {
            $h = ($factor_reduc!=NULL) ? $h0*$factor_reduc : $h0;
            if ($h > $maxHeight) {
                $factor_reduc = $maxHeight / $h;
                $h = $maxHeight;
                $w = $w * $factor_reduc;
            }
        }
        return [$w/5, $h/5];
    }

    private function getImgReduction($file, $p) {
        list($w, $h) = getimagesize($file);
        if ($w > $this->getMaxImgSize()) {
            $wreduc = $this->getMaxImgSize() / $w;
        }
        if ($h > $this->getMaxImgSize()) {
            $hreduc = $this->getMaxImgSize() / $h;
        }
        $r0 = ($wreduc < $hreduc) ? $wreduc : $hreduc;

        $wreduc = $hreduc = 1;
        if ($p['w'] && $p['w'] > $this->getMaxImgSize()) {
            $wreduc = $this->getMaxImgSize() / $p['w'];
        }
        if ($p['h'] && $p['h'] > $this->getMaxImgSize()) {
            $hreduc = $this->getMaxImgSize() / $p['h'];
        }
        $r1 = ($wreduc < $hreduc) ? $wreduc : $hreduc;

        return ($r0 < $r1) ? $r0 : $r1;
    }
    
    private function _getTextAlignAttrFromCurrentStyle($align){
        $mapValues = array("L" => "left", "R" => "right", "C"=>"center", "J"=>"justify", "left" => "left", "right" => "right", "center"=>"center", "justify"=>"justify");
        $value = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::ALIGN, $align);
        return "text-align:{$mapValues[$value]}; ";
    }

    protected function getContent($content ) {
        $pre_active=false;
        $aux="";
        $char = "";
        $ret = "";
        $this->style->goInTextContainer($content["type"]);
        $this->iocTcPdf->setFontFromCurrentStyle($this->pagesFont, "", $this->pagesFontSize);
        $this->iocTcPdf->setCellPaddingsFromCurrentStyle();
        $this->iocTcPdf->setCellMarginsFromCurrentStyle();
        $this->iocTcPdf->setBorderFromCurrentStyle();
        $this->iocTcPdf->setFillColorFromCurrentStyle();  
        $this->iocTcPdf->setPositonFromCurrentStyle();
        switch ($content["type"]) {
            case ListItemNodeDoc::LIST_ITEM_TYPE:
                $textAlign = $this->_getTextAlignAttrFromCurrentStyle("justify");
                $ret = "<li style=\"$textAlign\">".trim($this->getStructuredContent($content), " ")."</li>";
                break;
            case StructuredNodeDoc::DELETED_TYPE:
                $ret = "<del>".$this->getStructuredContent($content)."</del>";
                break;
            case StructuredNodeDoc::EMPHASIS_TYPE:
                $ret = "<em>".$this->getStructuredContent($content)."</em>";
                break;
            case StructuredNodeDoc::FOOT_NOTE_TYPE:
                break;
            case StructuredNodeDoc::LIST_CONTENT_TYPE:
                break;
            case StructuredNodeDoc::MONOSPACE_TYPE:
                $ret = "<code>".$this->getStructuredContent($content)."</code>";
                break;
            case StructuredNodeDoc::ORDERED_LIST_TYPE:
                $ret = "<ol>".$this->getStructuredContent($content)."</ol>";
                break;
            case StructuredNodeDoc::PARAGRAPH_TYPE:
                $textAlign = $this->_getTextAlignAttrFromCurrentStyle("justify");
                $ret = "<p style=\"$textAlign\">".trim($this->getStructuredContent($content), " ").'</p>';
                break;
            case StructuredNodeDoc::SINGLEQUOTE_TYPE:
                $char = "'";
            case StructuredNodeDoc::DOUBLEQUOTE_TYPE:
                $char = empty($char) ? "\"" : $char;
                $ret = $char.$this->getStructuredContent($content).$char;
                break;
            case StructuredNodeDoc::QUOTE_TYPE:
                $ret = "<blockquote>".$this->getStructuredContent($content)."</blockquote>";
                break;
            case StructuredNodeDoc::STRONG_TYPE:
                $ret = "<strong>".$this->getStructuredContent($content)."</strong>";
                break;
            case StructuredNodeDoc::SUBSCRIPT_TYPE:
                $ret = "<sub>".$this->getStructuredContent($content)."</sub>";
                break;
            case StructuredNodeDoc::SUPERSCRIPT_TYPE:
                $ret = "<sup>".$this->getStructuredContent($content)."</sup>";
                break;
            case StructuredNodeDoc::UNDERLINE_TYPE:
                $ret = "<u>".$this->getStructuredContent($content)."</u>";
                break;
            case StructuredNodeDoc::UNORDERED_LIST_TYPE:
                $ret = "<ul>".$this->getStructuredContent($content)."</ul>";
                break;
            case SpecialBlockNodeDoc::HIDDENCONTAINER_TYPE:
                $ret = '<span style="color:gray; font-size:80%;">' . $this->getStructuredContent($content) . '</span>';
                break;

            case LatexMathNodeDoc::LATEX_MATH_TYPE:
                $div = $nodiv = "";
                if ($content['class'] === 'blocklatex') {
                    $textAlign= $this->_getTextAlignAttrFromCurrentStyle("center");
                    $div = "<div style=\"margin:auto; $textAlign\">";
                    $nodiv = "</div>";
                }
                preg_match("|.*".DOKU_BASE."(.*)|", $content["src"], $t);
                $ret = $div . ' <img src="'.DOKU_BASE.$t[1].'"';
                if ($content["title"])
                    $ret.= ' alt="'.$content["title"].'"';
                if ($content["width"])
                    $ret.= ' width="'.$content["width"].'"';
                if ($content["height"])
                    $ret.= ' height="'.$content["height"].'"';
                $ret.= '> ' . $nodiv;
                break;

            case ImageNodeDoc::IMAGE_TYPE:
                if (preg_match("|\.gif$|", $content["src"], $t)) {
                    //El formato GIF no está soportado
                    $ret = " {$content["title"]} ";
                }else {
                    preg_match("|.*".DOKU_BASE."(.*)|", $content["src"], $t);
                    $reduc = $this->getImgReduction($content["src"], ['w'=>$content["width"], 'h'=>$content["height"]]);

                    $ret = ' <img src="'.DOKU_BASE.$t[1].'"';
                    if ($content["title"])
                        $ret.= ' alt="'.$content["title"].'"';
                    if ($content["width"])
                        $ret.= ' width="' . $content["width"]*$reduc . '"';
                    if ($content["height"])
                        $ret.= ' height="' . $content["height"]*$reduc . '"';
                    $ret.= '> ';
                }
                break;

            case LeafNodeDoc::ACRONYM_TYPE:
                $ret = $content['acronym'];
                break;
            case SmileyNodeDoc::SMILEY_TYPE:
                preg_match("|.*".DOKU_BASE."(.*)|", $content["src"], $t);
                $ret = ' <img src="'.DOKU_BASE.$t[1].'" alt="smiley" height="8" width="8"> ';
                break;

            case SpecialBlockNodeDoc::NEWCONTENT_TYPE:
                //$ret = '<div style="border:1px solid red; padding:0 10px; margin:5px 0;">' . $this->getStructuredContent($content) . "</div>";
            case SpecialBlockNodeDoc::BLOCVERD_TYPE:
                //$ret = '<span style="background-color:lightgreen;">' . $this->getStructuredContent($content) . '</span>';
            case SpecialBlockNodeDoc::PROTECTED_TYPE:
            case SpecialBlockNodeDoc::SOL_TYPE:
            case SpecialBlockNodeDoc::SOLUCIO_TYPE:
            case SpecialBlockNodeDoc::VERD_TYPE:
            case SpecialBlockNodeDoc::EDITTABLE_TYPE:
                $ret = $this->getStructuredContent($content);
                break;
            case IocElemNodeDoc::IOC_ELEM_TYPE:
                $bcolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BACKGROUND_COLOR, FALSE);
                $this->style->goInTextContainer($content["elemType"]);
                $this->iocTcPdf->setFontFromCurrentStyle($this->pagesFont, "", $this->pagesFontSize);
                $this->iocTcPdf->setCellPaddingsFromCurrentStyle();
                $this->iocTcPdf->setCellMarginsFromCurrentStyle();
                $this->iocTcPdf->setBorderFromCurrentStyle();
                $this->iocTcPdf->setFillColorFromCurrentStyle();
                $this->iocTcPdf->setPositonFromCurrentStyle();   
                $this->iocTcPdf->setIconContainerToRender($content["elemType"]);
                switch ($content["elemType"]){
                    case IocElemNodeDoc::IOC_ELEM_TYPE_EXAMPLE:
                        $fs = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::FONT_SIZE, 9)*1.5;
                        $aux=" font-size: {$fs}px;";
                    case IocElemNodeDoc::IOC_ELEM_TYPE_COMP_LARGE:
                        if($content["elemType"]=== IocElemNodeDoc::IOC_ELEM_TYPE_EXAMPLE){
                            $bcolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BACKGROUND_COLOR, $bcolor);                            
                        }else{
                            $bcolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BACKGROUND_COLOR, "#efefef");                            
                        }
                        $bc = "";
                        if($bcolor){
                            $bc = " background-color: $bcolor;";
                        }
                        $p_style="style=\"$aux\"";
                        $title = $content["title"];
                        $brd = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDER, FALSE);
                        $brdt = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR_TOP, $brd);
                        $brdb = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR_TOP, $brd);
                        if($brd||$brdb||$brdt){
                            $bordercolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR, "#ccc");
                            $bordercolortop = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR_TOP, $bordercolor);
                            $bordercolorbootom = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR_BOTTOM, $bordercolor);
                            $borderstyle = " border-top: 1px solid $bordercolortop; border-bottom: 1px solid $bordercolorbootom;\"";
                        }else{
                            $borderstyle="";                        
                        }
                        $ret .= "<div style=\"$bc$borderstyle\">";
                        $ret .= "<p $p_style><strong>$title</strong></p>";
                        $ret .= self::getStructuredContent($content);
//                        $ret .= "</div></div>";
                        $ret .= "</div>";
                        break;
                    case IocElemNodeDoc::IOC_ELEM_TYPE_QUOTE:
                    case IocElemNodeDoc::IOC_ELEM_TYPE_IMPORTANT:
                        if($content["type"]=== IocElemNodeDoc::IOC_ELEM_TYPE_QUOTE){
                            $bcolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BACKGROUND_COLOR, "#efefef");                            
                            $color = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::FONT_COLOR, "#2c2c2c");                            
                            $brd = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDER, FALSE);
                            $brdt = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR_TOP, $brd);
                            $brdb = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR_TOP, $brd);
                            if($brd||$brdb||$brdt){
                                $bordercolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR, "#ccc");
                                $bordercolortop = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR_TOP, $bordercolor);
                                $bordercolorbootom = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BORDERCOLOR_BOTTOM, $bordercolor);
                                $borderstyle = " border-top: 1px solid $bordercolortop; border-bottom: 1px solid $bordercolorbootom;\"";
                            }else{
                                $borderstyle="";                        
                            }
                            $bc = " background-color:$bcolor; color:$color;$borderstyle";
                        }else{
                            $bcolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BACKGROUND_COLOR, "#ccc");                            
                            $bc = " background-color: $bcolor;";
                        }
                        $ret = "<div nobr=\"true\" style=\"$bc\">";
                        $ret .= self::getStructuredContent($content);
                        $ret .= "</div>";
                        break;
                    case IocElemNodeDoc::IOC_ELEM_TYPE_COMP:
                        $aux ="<p><strong>{$content["title"]}</strong></p>";                        
                    case IocElemNodeDoc::IOC_ELEM_TYPE_NOTE:
                    case IocElemNodeDoc::IOC_ELEM_TYPE_REF:
                        $bcolor = $this->style->getCurrentContainerStyleAttr(TcPdfStyle::BACKGROUND_COLOR, "#ccc");                            
                        $ret = "<div nobr=\"true\" style=\"background-color: $bcolor;\">$aux";
                        $ret .= $this->getStructuredContent($content);
                        $ret .= "</div>";
                        break;
                }
                $this->style->goOutTextContainer();
                break;
            case TableFrame::TABLEFRAME_TYPE_TABLE:
            case TableFrame::TABLEFRAME_TYPE_ACCOUNTING:
                if ($content['widths']) {
                    $e = explode(',', $content['widths']);
                    $t = 0;
                    for ($i=0; $i<count($e); $i++) $t += $e[$i];
                    for ($i=0; $i<count($e); $i++) $this->tablewidths[$i] = $e[$i] * 100 / $t;
                }
                $ret = "<div nobr=\"true\">";
                if ($content["title"]) {
                    $ret .= "<h4 style=\"text-align:center;\"> Taula ".$this->tableReferences[$content["id"]].". ".$content["title"]."</h4>";
                }
                $ret .= $this->getStructuredContent($content);
                if ($content["footer"]) {
                    if ($content["title"]) {
                        $ret .= "<p style=\"text-align:justify; font-size:80%;\">".$content["footer"]."</p>";
                    }else {
                        $ret .= "<p style=\"text-align:justify; font-size:80%;\"> Taula ".$this->tableReferences[$content["id"]].". ".$content["footer"]."</p>";
                    }
                }
                $ret .= "</div>";
                break;
            case TableNodeDoc::TABLE_TYPE:
                $ret = '<table cellpadding="5" nobr="true">'.$this->getStructuredContent($content)."</table>";
                break;
            case StructuredNodeDoc::TABLEROW_TYPE:
                $ret = "<tr>".$this->getStructuredContent($content)."</tr>";
                $this->nColInRow = 0;
                break;
            case CellNodeDoc::TABLEHEADER_TYPE:
                $align = $content["align"] ? "text-align:{$content["align"]};" : "text-align:center;";
                $style = $content["hasBorder"] ? ' style="border:1px solid black; border-collapse:collapse; '.$align.' font-weight:bold; background-color:#F0F0F0;"' : ' style="'.$align.' font-weight:bold; background-color:#F0F0F0;"';
                $colspan = $content["colspan"]>1 ? ' colspan="'.$content["colspan"].'"' : "";
                $rowspan = $content["rowspan"]>1 ? ' rowspan="'.$content["rowspan"].'"' : "";
                $ret = "<th$colspan$rowspan$style>".$this->getStructuredContent($content)."</th>";
                break;
            case CellNodeDoc::TABLECELL_TYPE:
                $align = $content["align"] ? "text-align:{$content["align"]};" : "text-align:center;";
                $style = $content["hasBorder"] ? ' style="border:1px solid black; border-collapse:collapse; '.$align.'"' : " style=\"$align\"";
                $colspan = $content["colspan"]>1 ? ' colspan="'.$content["colspan"].'"' : "";
                $rowspan = $content["rowspan"]>1 ? ' rowspan="'.$content["rowspan"].'"' : "";
                $width =  ($this->tablewidths[$this->nColInRow++]) ? ' with="'.$this->tablewidths[$this->nColInRow++].'%"' : "";
                $ret = "<td$colspan$rowspan$style$width>".$this->getStructuredContent($content)."</td>";
                break;
            case TextNodeDoc::HTML_TEXT_TYPE:
                $ret = $this->getTextContent($content);
                break;
            case TextNodeDoc::PLAIN_TEXT_TYPE:
                $ret = $this->getTextContent($content);
                break;

            case ReferenceNodeDoc::REFERENCE_TYPE:
                $titol = (empty($content["referenceTitle"])) ? $content["referenceId"] : $content["referenceTitle"];
                $this->style->goInTextContainer($content["referenceType"]);
                $this->iocTcPdf->setFontFromCurrentStyle($this->pagesFont, "", $this->pagesFontSize);
                $this->iocTcPdf->setCellPaddingsFromCurrentStyle();
                $this->iocTcPdf->setCellMarginsFromCurrentStyle();
                $this->iocTcPdf->setBorderFromCurrentStyle();
                $this->iocTcPdf->setFillColorFromCurrentStyle(); 
                $this->iocTcPdf->setPositonFromCurrentStyle();
                switch ($content["referenceType"]) {
                    case ReferenceNodeDoc::REF_TABLE_TYPE:
                        $id = trim($content["referenceId"]);
                        $ret = " <a href=\"#".$id."\"><em>Taula ".$this->tableReferences[$id]."</em></a> ";
                        break;
                    case ReferenceNodeDoc::REF_FIGURE_TYPE:
                        $id = trim($content["referenceId"]);
                        $ret = " <a href=\"#".$id."\"><em>Figura ".$this->figureReferences[$id]."</em></a> ";
                        break;
                    case ReferenceNodeDoc::REF_WIKI_LINK:
                        $file = $_SERVER['HTTP_REFERER']."?id=".$content["referenceId"];
                        $ret = " <a href=\"".$file."\">".$titol."</a> ";
                        break;
                    case ReferenceNodeDoc::REF_INTERNAL_LINK:
                        $ret = " <a href='".$content["referenceId"]."'>".$titol."</a> ";
                        break;
                    case ReferenceNodeDoc::REF_EXTERNAL_LINK:
                        $ret = " <a href=\"".$content["referenceId"]."\">".$titol."</a> ";
                        break;
                }
                $this->style->goOutTextContainer();
                break;

            case CodeNodeDoc::CODE_TEXT_TYPE:
                //$content["text"] = p_xhtml_cached_geshi($content["text"], $content["language"], "code");
            case TextNodeDoc::UNFORMATED_TEXT_TYPE:
            case TextNodeDoc::PREFORMATED_TEXT_TYPE:
                $ret = "<pre>".$this->getPreformatedTextContent($content)."</pre>";
                break;
            default :
                $ret = $this->getLeafContent($content);
        }
        $this->style->goOutTextContainer();
        return $ret;
    }

    protected function getStructuredContent($content) {
        $ret = "";
        $limit = count($content["content"]);
        for ($i=0; $i<$limit; $i++) {
            $ret .= $this->getContent($content["content"][$i]);
        }
        return $ret;
    }

    protected function getPreformatedTextContent($content) {
        if (!empty($content["text"]) && empty(trim($content["text"]))) {
            $ret = " ";
        }else {
            $ret = preg_replace(array("/<br>/", "/&#92;/"), array("\n", "\\"), trim($content["text"]));  
            $ret = htmlspecialchars($ret, ENT_QUOTES, 'UTF-8');
        }
        return $ret;
    }
    
    protected function getTextContent($content) {
        if (!empty($content["text"]) && empty(trim($content["text"]))) {
            $ret = " ";
        }else {
            $ret = preg_replace("/\s\s+/", " ", $content["text"]);
            $ret = htmlspecialchars($ret, ENT_QUOTES, 'UTF-8');
        }
        return $ret;
    }

    protected function getLeafContent($content) {
        switch($content["type"]) {
            case LeafNodeDoc::HORIZONTAL_RULE_TYPE:
                $ret = "<br pagebreak=\"true\"/>";
                break;
            case LeafNodeDoc::LINE_BREAK_TYPE:
                $ret = "<br>";
                break;
            case LeafNodeDoc::DOUBLEAPOSTROPHE_TYPE:
                $ret = "\"";
                break;
            case LeafNodeDoc::APOSTROPHE_TYPE:
            case LeafNodeDoc::OP_SINGLEQUOTE_TYPE:
            case LeafNodeDoc::CL_SINGLEQUOTE_TYPE:
                $ret = "'";
                break;
            case LeafNodeDoc::BACKSLASH_TYPE:
                $ret = "\\";
                break;
            case LeafNodeDoc::DOUBLEHYPHEN_TYPE:
                $ret = "&mdash;";
                break;
            case LeafNodeDoc::GRAVE_TYPE:
                $ret = "&#96;";
                break;
        }
        return $ret;
    }

    public static function getText($text, $max, IocTcPdf &$iocTcPdf){
        if($iocTcPdf->GetStringWidth($text)>$max){
            while($iocTcPdf->GetStringWidth($text."...")>$max){
                $text = substr($text, 0, strlen($text)-1);
            }
            $text = $text."...";
        }
        return $text;
    }

    
    protected function getPdfStyleFromFile($filePath) {
        $estils = TcPdfStyle::EMPTY_STYLE_VALUES;
        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            $estils =json_decode($json, true);
        }

        return $estils;
    }            
}