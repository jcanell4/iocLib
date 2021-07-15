<?php
/**
 * GetTocAction: Obtiene la Table Of Contents de una página
 * @author rafael <rclaver@ioc.cat>
 */
if (!defined("DOKU_INC")) die();

class GetTocAction extends PageAction {

    protected function startProcess() {}
    protected function runProcess() {}

    public function responseProcess() {
        $toc = $this->getModel()->getMetaToc();
        $toc = json_encode(['htmlTOC' => preg_replace(['/<!--.*-->\n*/', '/\n/'], '', $toc)]);
        return $toc;
    }

}
