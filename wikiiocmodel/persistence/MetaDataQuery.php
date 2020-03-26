<?php
/**
 * Description of MetaDataQuery
 * @author josep
 */
if (! defined('DOKU_INC')) die();
require_once (DOKU_INC . 'inc/pageutils.php');

class MetaDataQuery extends DataQuery {

    public function getFileName($id, $sppar) {
        if ($sppar && isset($sppar["ext"])){
            $ext = $sppar["ext"];
        }else{
            $ext ="";
        }
        return metaFN($id, $ext);
    }

    public function getNsTree($currentNode, $sortBy, $onlyDirs=FALSE, $expandProjects=FALSE, $hiddenProjects=FALSE, $root=FALSE) {
        global $conf;
        $base = $conf['metadir'];
        return $this->getNsTreeFromGenericSearch($base, $currentNode, $sortBy, $onlyDirs, 'search_index', $expandProjects, $hiddenProjects, $root);
    }

}
