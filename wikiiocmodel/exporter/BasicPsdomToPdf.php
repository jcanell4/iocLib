<?php

/*BasicStyle
{
    align: L|left|R|right|C|center
    padding: [0,+inf) | {top: [0,+inf), right: [0,+inf), bottom: [0,+inf), left: [0,+inf)}
    margin: [0,+inf) | {top: [0,+inf), right: [0,+inf), bottom: [0,+inf), left: [0,+inf)}
    border: [0,+inf) | {top: [0,+inf), right: [0,+inf), bottom: [0,+inf), left: [0,+inf)}
    color: [#000000, #ffffff]
    background-color:[#000000, #ffffff]
    font: (font name list)
    font-size: [0,+inf)    
    pos-x: [0,+inf)
    pos-y: [0,+inf)
    width: [0,+inf)
    height:  [0,+inf)
}
*/

/*RegexStyle
{
    type: content|...
    keyToReplace: @@CONTENT@@|...
    textBase: [STRING contenint la clau keyToReplace]
}
*/


class TcpPdfStyle{
    const BASIC_STYLE = "BasicStyle";
    const REGEX_STYLE = "RegexStyle";
    const STYLE_DEF = "StyleDef";
    const DEFAULT_STYLE = "DefaultStyle";
    const STRUCTURED_STYLE = "StructuredStyle";
 

    private $styleDefs;
    
    function setStyle($styleStructure){
        
    }
    
}

class BasicPdfRenderer {
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
    protected $state = ["table" => ["type" => "table"]];
    protected $tcpdfObj = NULL;
    protected $maxImgSize = 100;

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
        $this->state = ["table" => ["type" => "table"]];
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
                self::resolveReferences($content["content"][$i]);
            }
        }
        if (!empty($content["children"])) {
            for ($i=0; $i<count($content["children"]); $i++) {
                self::resolveReferences($content["children"][$i]);
            }
        }
    }

    protected function renderHeader($header, IocTcPdf &$iocTcPdf) {
        if ($header['type'] !== StructuredNodeDoc::ROOTCONTENT_TYPE) {
            $level = $header["level"]-1;
            $iocTcPdf->SetFont('Times', 'B', 12);
            $title = self::incHeaderCounter($level).$header["title"];

            //Control de espacio disponible para impedir títulos huérfanos
            if ($iocTcPdf->GetY() + 40 > $iocTcPdf->getPageHeight()) {
                $iocTcPdf->AddPage(); //inserta salto de pagina
            }

            $iocTcPdf->Bookmark($title, $level, 0);
            $iocTcPdf->Ln(5);
            $iocTcPdf->Cell(0, 0, $title, 0,1, "L");
            $iocTcPdf->Ln(3);
        }

        if (!empty($header["content"])) {
            for ($i=0; $i<count($header["content"]); $i++) {
                self::renderContent($header["content"][$i], $iocTcPdf);
            }
        }
        if (!empty($header["children"])) {
            for ($i=0; $i<count($header["children"]); $i++) {
                self::renderHeader($header["children"][$i], $iocTcPdf);
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
        return self::getHeaderCounter($level);
    }

    protected function renderContent($content, IocTcPdf &$iocTcPdf, $pre="", $post="") {
        $iocTcPdf->SetFont('helvetica', '', 10);
        if ($content['type'] === FigureFrame::FRAME_TYPE_FIGURE) {
            self::getFrameContent($content, $iocTcPdf);
        }/*
        elseif ($content['type'] === StructuredNodeDoc::PARAGRAPH_TYPE && $content['content'][0]['type'] === ImageNodeDoc::IMAGE_TYPE) {
            self::renderImage($content, $iocTcPdf);
        }
        elseif ($content['type'] === ImageNodeDoc::IMAGE_TYPE) {
            self::renderImage($content, $iocTcPdf);
        }
        elseif ($content['type'] === SmileyNodeDoc::SMILEY_TYPE) {
            self::renderSmiley($content, $iocTcPdf);
        }*/
        else {
            $ret = static::getContent($content);
            self::_cleanWriteHTML($ret, $iocTcPdf);
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
                self::renderImage($content, $iocTcPdf);
                break;

            case FigureFrame::FRAME_TYPE_FIGURE:
                // Comprueba si queda suficiente espacio vertical para poner la imagen
                // junto al título, es decir, si cabe el bloque título + imagen en el resto de página
                list($w, $h) = self::setImageSize($content['content'][0]['content'][0]['src'], $content['content'][0]['content'][0]['width'], $content['content'][0]['content'][0]['height']);
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
                self::_cleanWriteHTML($ret, $iocTcPdf);
                $ret = self::getFrameStructuredContent($content, $iocTcPdf);
                if ($content['footer']) {
                    if ($content['title']) {
                        $ret .= "<p $center font-size:80%;\">".$content['footer']."</p>";
                    }else {
                        $ret .= "<p $center font-size:80%;\">Figura ".$this->figureReferences[$content['id']].". ".$content['footer']."</p>";
                    }
                }
                $ret .= "</div>";
                self::_cleanWriteHTML($ret, $iocTcPdf);
                break;

            default:
                self::getFrameStructuredContent($content, $iocTcPdf);
                break;
        }
        return "";
    }

    protected function getFrameStructuredContent($content, IocTcPdf &$iocTcPdf) {
        $ret = "";
        $limit = count($content['content']);
        for ($i=0; $i<$limit; $i++) {
            $ret .= self::getFrameContent($content['content'][$i], $iocTcPdf);
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
    private function _cleanWriteHTML($content, IocTcPdf &$iocTcPdf, $ln=TRUE, $fill=FALSE, $reseth=false, $cell=false, $align='') {
        $c = 0;
        $aSearch = ["/0xFF/", "/0xFEFF/"];
        $aReplace = [" ", " "];
        $content = preg_replace($aSearch, $aReplace, $content, -1, $c);
        if ($c > 0) {
            $content = str_replace($aSearch, $aReplace, $content);
        }
        $iocTcPdf->writeHTML($content, $ln, $fill, $reseth, $cell, $align);
    }

    private function renderSmiley($content, IocTcPdf &$iocTcPdf) {
        preg_match('/\.(.+)$/', $content['src'], $match);
        $ext = ($match) ? $match[1] : "JPG";
        $iocTcPdf->Image($content['src'], '', '', 0, 0, $ext, '', 'T');
    }

    protected function renderImage($content, IocTcPdf &$iocTcPdf) {
        preg_match('/\.(.+)$/', $content['src'], $match);
        $ext = ($match) ? $match[1] : "JPG";
        //càlcul de les dimensions de la imatge
        list($w, $h) = self::setImageSize($content['src'], $content['width'], $content['height']);
        if ($iocTcPdf->GetY() + $h > $iocTcPdf->getPageHeight()) {
            $iocTcPdf->AddPage(); //inserta salto de pagina
        }
        //inserció de la imatge
        $iocTcPdf->Image($content['src'], '', '', $w, 0, $ext, '', 'T', true, 300, 'C');
        $iocTcPdf->SetY($iocTcPdf->GetY() + $h); //correcció de la coordinada Y desprès de insertar la imatge
        //inserció del títol a sota de la imatge
        $center = "style=\"margin:auto; text-align:center;";
        $text = "<p $center font-size:80%;\">{$content['title']}</p>";
        self::_cleanWriteHTML($text, $iocTcPdf);
    }

    private function setImageSize($imageFile, $w=NULL, $h=NULL) {
        if (@file($imageFile)) {
            list($w0, $h0) = getimagesize($imageFile);
        }
        if ($w0 == NULL) {
            $w0 = $h0 = 5;
        }

        if ($w==NULL) {
            if ($w0 <= 800) {
                $w = $w0;
            }else {
                $factor_reduc = 800 / $w0;
                $w = 800;
            }
        }else {
            $factor_reduc = $w / $w0;
        }
        if ($h==NULL) {
            $h = ($factor_reduc!=NULL) ? $h0*$factor_reduc : $h0;
            if ($h > 1200) {
                $factor_reduc = 1200 / $h;
                $h = 1200;
                $w = $w * $factor_reduc;
            }
        }
        return [$w/5, $h/5];
    }

    private function getImgReduction($file, $p) {
        list($w, $h) = getimagesize($file);
        if ($w > self::getMaxImgSize()) {
            $wreduc = self::getMaxImgSize() / $w;
        }
        if ($h > self::getMaxImgSize()) {
            $hreduc = self::getMaxImgSize() / $h;
        }
        $r0 = ($wreduc < $hreduc) ? $wreduc : $hreduc;

        $wreduc = $hreduc = 1;
        if ($p['w'] && $p['w'] > self::getMaxImgSize()) {
            $wreduc = self::getMaxImgSize() / $p['w'];
        }
        if ($p['h'] && $p['h'] > self::getMaxImgSize()) {
            $hreduc = self::getMaxImgSize() / $p['h'];
        }
        $r1 = ($wreduc < $hreduc) ? $wreduc : $hreduc;

        return ($r0 < $r1) ? $r0 : $r1;
    }

    protected function getContent($content ) {
        $pre_active=false;
        $aux="";
        $char = "";
        $ret = "";
        switch ($content["type"]) {
            case ListItemNodeDoc::LIST_ITEM_TYPE:
                $ret = "<li style=\"text-align:justify;\">".trim(self::getStructuredContent($content), " ")."</li>";
                break;
            case StructuredNodeDoc::DELETED_TYPE:
                $ret = "<del>".self::getStructuredContent($content)."</del>";
                break;
            case StructuredNodeDoc::EMPHASIS_TYPE:
                $ret = "<em>".self::getStructuredContent($content)."</em>";
                break;
            case StructuredNodeDoc::FOOT_NOTE_TYPE:
                break;
            case StructuredNodeDoc::LIST_CONTENT_TYPE:
                break;
            case StructuredNodeDoc::MONOSPACE_TYPE:
                $ret = "<code>".self::getStructuredContent($content)."</code>";
                break;
            case StructuredNodeDoc::ORDERED_LIST_TYPE:
                $ret = "<ol>".self::getStructuredContent($content)."</ol>";
                break;
            case StructuredNodeDoc::PARAGRAPH_TYPE:
                $ret = '<p style="text-align:justify;">'.trim(self::getStructuredContent($content), " ").'</p>';
                break;
            case StructuredNodeDoc::SINGLEQUOTE_TYPE:
                $char = "'";
            case StructuredNodeDoc::DOUBLEQUOTE_TYPE:
                $char = empty($char) ? "\"" : $char;
                $ret = $char.self::getStructuredContent($content).$char;
                break;
            case StructuredNodeDoc::QUOTE_TYPE:
                $ret = "<blockquote>".self::getStructuredContent($content)."</blockquote>";
                break;
            case StructuredNodeDoc::STRONG_TYPE:
                $ret = "<strong>".self::getStructuredContent($content)."</strong>";
                break;
            case StructuredNodeDoc::SUBSCRIPT_TYPE:
                $ret = "<sub>".self::getStructuredContent($content)."</sub>";
                break;
            case StructuredNodeDoc::SUPERSCRIPT_TYPE:
                $ret = "<sup>".self::getStructuredContent($content)."</sup>";
                break;
            case StructuredNodeDoc::UNDERLINE_TYPE:
                $ret = "<u>".self::getStructuredContent($content)."</u>";
                break;
            case StructuredNodeDoc::UNORDERED_LIST_TYPE:
                $ret = "<ul>".self::getStructuredContent($content)."</ul>";
                break;
            case SpecialBlockNodeDoc::HIDDENCONTAINER_TYPE:
                $ret = '<span style="color:gray; font-size:80%;">' . self::getStructuredContent($content) . '</span>';
                break;

            case LatexMathNodeDoc::LATEX_MATH_TYPE:
                $div = $nodiv = "";
                if ($content['class'] === 'blocklatex') {
                    $div = "<div style=\"margin:auto; text-align:center;\">";
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
                    $reduc = self::getImgReduction($content["src"], ['w'=>$content["width"], 'h'=>$content["height"]]);

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
                //$ret = '<div style="border:1px solid red; padding:0 10px; margin:5px 0;">' . self::getStructuredContent($content) . "</div>";
            case SpecialBlockNodeDoc::BLOCVERD_TYPE:
                //$ret = '<span style="background-color:lightgreen;">' . self::getStructuredContent($content) . '</span>';
            case SpecialBlockNodeDoc::PROTECTED_TYPE:
            case SpecialBlockNodeDoc::SOL_TYPE:
            case SpecialBlockNodeDoc::SOLUCIO_TYPE:
            case SpecialBlockNodeDoc::VERD_TYPE:
            case SpecialBlockNodeDoc::EDITTABLE_TYPE:
                $ret = self::getStructuredContent($content);
                break;
            case IocElemNodeDoc::IOC_ELEM_TYPE:
                switch ($content["elemType"]){
                    case IocElemNodeDoc::IOC_ELEM_TYPE_EXAMPLE:
                        $aux=" font-size: 120%;";
                    case IocElemNodeDoc::IOC_ELEM_TYPE_COMP_LARGE:
                        if($content["type"]=== IocElemNodeDoc::IOC_ELEM_TYPE_EXAMPLE){
                            $bc = "";
                        }else{
                            $bc = " background-color: #efefef;";
                        }
                        $p_style="style=\"margin-bottom: 2em;$aux\"";
                        $title = $content["title"];
                        $ret = "<div nobr=\"true\" style=\"width: 73%;\">";
                        $ret .= "<div style=\"clear:both;$bc width: 80%; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; margin: 1em auto; font-size: 85%;\">";
                        $ret .= "<p $p_style>$title<\p>";
                        $ret .= self::getStructuredContent($content);
                        $ret .= "</div></div>";
                        break;
                    case IocElemNodeDoc::IOC_ELEM_TYPE_QUOTE:
                    case IocElemNodeDoc::IOC_ELEM_TYPE_IMPORTANT:
                        if($content["type"]=== IocElemNodeDoc::IOC_ELEM_TYPE_QUOTE){
                            $bc = " background-color: #efefef; padding: 1.5em 4.5em 2.5em 1.5em; font-size: 85%; color: #2c2c2c; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc;";
                        }else{
                            $bc = " background-color: #ccc; padding: 10mm;";
                        }
                        $ret = "<div nobr=\"true\" style=\"clear:both; width: 80%;$bc margin: 10mm auto;\">";
                        $ret .= self::getStructuredContent($content);
                        $ret .= "</div>";
                        break;
                    case IocElemNodeDoc::IOC_ELEM_TYPE_COMP:
                    case IocElemNodeDoc::IOC_ELEM_TYPE_NOTE:
                    case IocElemNodeDoc::IOC_ELEM_TYPE_REF:
                        $ret = "<div nobr=\"true\" style=\"clear:both; width: 80%; background-color: #ccc; padding: 10mm; margin: 10mm auto;\">";
                        $ret .= self::getStructuredContent($content);
                        $ret .= "</div>";
                        break;
                }
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
                $ret .= self::getStructuredContent($content);
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
                $ret = '<table cellpadding="5" nobr="true">'.self::getStructuredContent($content)."</table>";
                break;
            case StructuredNodeDoc::TABLEROW_TYPE:
                $ret = "<tr>".self::getStructuredContent($content)."</tr>";
                $this->nColInRow = 0;
                break;
            case CellNodeDoc::TABLEHEADER_TYPE:
                $align = $content["align"] ? "text-align:{$content["align"]};" : "text-align:center;";
                $style = $content["hasBorder"] ? ' style="border:1px solid black; border-collapse:collapse; '.$align.' font-weight:bold; background-color:#F0F0F0;"' : ' style="'.$align.' font-weight:bold; background-color:#F0F0F0;"';
                $colspan = $content["colspan"]>1 ? ' colspan="'.$content["colspan"].'"' : "";
                $rowspan = $content["rowspan"]>1 ? ' rowspan="'.$content["rowspan"].'"' : "";
                $ret = "<th$colspan$rowspan$style>".self::getStructuredContent($content)."</th>";
                break;
            case CellNodeDoc::TABLECELL_TYPE:
                $align = $content["align"] ? "text-align:{$content["align"]};" : "text-align:center;";
                $style = $content["hasBorder"] ? ' style="border:1px solid black; border-collapse:collapse; '.$align.'"' : " style=\"$align\"";
                $colspan = $content["colspan"]>1 ? ' colspan="'.$content["colspan"].'"' : "";
                $rowspan = $content["rowspan"]>1 ? ' rowspan="'.$content["rowspan"].'"' : "";
                $width =  ($this->tablewidths[$this->nColInRow++]) ? ' with="'.$this->tablewidths[$this->nColInRow++].'%"' : "";
                $ret = "<td$colspan$rowspan$style$width>".self::getStructuredContent($content)."</td>";
                break;
            case TextNodeDoc::HTML_TEXT_TYPE:
                $ret = self::getTextContent($content);
                break;
            case TextNodeDoc::PLAIN_TEXT_TYPE:
                $ret = self::getTextContent($content);
                break;

            case ReferenceNodeDoc::REFERENCE_TYPE:
                $titol = (empty($content["referenceTitle"])) ? $content["referenceId"] : $content["referenceTitle"];
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
                break;

            case CodeNodeDoc::CODE_TEXT_TYPE:
                //$content["text"] = p_xhtml_cached_geshi($content["text"], $content["language"], "code");
            case TextNodeDoc::UNFORMATED_TEXT_TYPE:
            case TextNodeDoc::PREFORMATED_TEXT_TYPE:
                $ret = "<pre>".self::getPreformatedTextContent($content)."</pre>";
                break;
            default :
                $ret = self::getLeafContent($content);
        }
        return $ret;
    }

    protected function getStructuredContent($content) {
        $ret = "";
        $limit = count($content["content"]);
        for ($i=0; $i<$limit; $i++) {
            $ret .= static::getContent($content["content"][$i]);
        }
        return $ret;
    }

    protected function getPreformatedTextContent($content) {
        if (!empty($content["text"]) && empty(trim($content["text"]))) {
            $ret = " ";
        }else {
            $ret = preg_replace(array("/<br>/"), array("\n"), trim($content["text"]));  
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
                $ret = "<hr>";
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

}