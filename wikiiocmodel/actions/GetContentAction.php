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
        $section = $this->params['selected'];
        $idSection = $this->params['idSection'];
        $data = $this->getModel()->getData(TRUE);
        if (preg_match('/(<h([1-9]) class=\"sectionedit([1-9])\" id=\"'.$idSection.'\">'.$section.'<\/h\2>)'
                      .'(\n.*)+'
                      .'(<!-- EDIT\3 SECTION \"'.$section.'\".*-->)/', $data['structure']['html'], $match)) {
            $capitol = str_replace($match[5], "", $match[0]);
        }
//        $p = $data['structure']['dictionary'][$this->params['idSection']];
//        $start = $data['structure']['chunks'][$p]['start'];
//        $end = $data['structure']['chunks'][$p]['end'];
//        $capitol = substr($data['structure']['html'], $start, $end-$start);
        $ret = json_encode(['htmlContent' => preg_replace(['/\n\n/', '/\n/'], '', $capitol),
                            'date' => $data['structure']['date']]);
        return $ret;
    }

}
