<?php
/**
 * GetTocAction: Obtiene la Table Of Contents de una página
 * @author rafael
 */
if (!defined("DOKU_INC")) die();

class GetTocAction extends PageAction {

    protected function startProcess() {
        parent::startProcess();
    }

    protected function runProcess() {}

    public function responseProcess() {
        $toc = $this->getModel()->getMetaToc();
        return $toc;
    }

}
