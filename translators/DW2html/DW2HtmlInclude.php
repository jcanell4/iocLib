<?php
require_once "DW2HtmlParser.php";

class DW2HtmlInclude extends DW2HtmlInstruction
{

    protected $parsingContent = false;

    public function open()
    {

        $token = $this->currentToken;


        // És page o section?
        $matches = null;
        $type = null;
        if (preg_match('/^{{(page|section)>/m', $token['raw'], $matches)) {
            $type = $matches[1];
        }

        $content = null;
        if (preg_match('/^{{.*>(.*?)}}$/m', $token['raw'], $matches)) {
            $content = $matches[1];
        }

        $post = "</div>";

        $pre = "<div class=\"iocinclude\" data-dw-include=\"$content\" data-dw-include-type=\"$type\" " .
            "contenteditable=\"false\" data-dw-highlighted=\"false\">";

        $value = "<span>incloent [$type]: $content</span>";

        return $pre . $value . $post;
    }

    public function isClosing($token)
    {

        return !$this->parsingContent;

    }

}
