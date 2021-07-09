<?php
/**
 * GetTocAction: Obtiene la Table Of Contents de una página
 * @author rafael <rclaver@ioc.cat>
 */
if (!defined("DOKU_INC")) die();

class GetContentAction extends PageAction {

    protected function startProcess() {}
    protected function runProcess() {}

    public function responseProcess() {
        $this->getModel()->init($this->params['id'], NULL, $this->params['selected']);
//        $this->params['target'] = "section";
//        $this->params['section_id'] = $this->params['idSection'];
        $data = $this->getModel()->getData(TRUE);
        $seccio = preg_replace($pattern, $replacement, $data);
        $p = $data['structure']['dictionary'][$this->params['idSection']];
        $start = $data['structure']['chunks'][$p]['start'];
        $end = $data['structure']['chunks'][$p]['end'];
        $capitol = substr($data['structure']['html'], $start, $end-$start);
        $ret = json_encode(['htmlContent' => preg_replace(['/\n\n/', '/\n/'], '', $capitol),
                            'date' => $data['structure']['date']]);
        return $ret;
    }

}
