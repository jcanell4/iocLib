<?php
/**
 * Description of PageDataQuery
 * @author josep
 */
if (! defined('DOKU_INC')) die();

require_once (DOKU_INC . 'inc/common.php');
require_once (DOKU_INC . 'inc/changelog.php');
require_once (DOKU_INC . 'inc/template.php');
require_once (DOKU_INC . 'inc/pageutils.php');
require_once (DOKU_INC . 'inc/parserutils.php');
require_once (DOKU_INC . 'inc/io.php');

class PageDataQuery extends DataQuery {

    public function getFileName($id, $specparams=NULL) {
        $clean=true;
        $rev = "";
        if(is_array($specparams)){
            if($specparams["clean"]){
                $clean=$specparams["clean"];
            }
            if($specparams["rev"]){
                $rev=$specparams["rev"];
            }
        }else{
            $rev = $specparams;
        }
        return wikiFN($id, $rev, $clean);
    }

    /**
     * És la crida principal de la comanda ns_tree_rest
     * @global type $conf
     * @param type $currentnode
     * @param type $sortBy
     * @param type $onlyDirs
     * @return type
     */
    public function getNsTree( $currentNode, $sortBy, $onlyDirs=FALSE, $expandProjects=FALSE, $hiddenProjects=FALSE, $root=FALSE, $subSetList=NULL ) {
        $base = WikiGlobalConfig::getConf('datadir');
        return $this->getNsTreeFromGenericSearch($base, $currentNode, $sortBy, $onlyDirs, 'search_index', $expandProjects, $hiddenProjects, $root, $subSetList);
    }


    public function getMetaFiles($id){
        return metaFiles($id);
    }

    public function save($id, $text, $summary, $minor = false, $forceSave=false){
        $fdt = @filemtime(wikiFN($id));
        saveWikiText($id, $text, $summary, $minor);
        if($forceSave && $fdt === filemtime(wikiFN($id))){
            saveWikiText($id, " ", "");
            saveWikiText($id, $text, $summary, $minor);
        }


        $partialDisabled = strpos($text, '~~USE:WIOCCL~~') !== false;

        $meta['partialDisabled'] = $partialDisabled;
        p_set_metadata($id, $meta);

    }

    public function getHtml($id, $rev = null){
        $html = $this->p_wiki_xhtml($id, $rev, true);

        return $html;

    }

    public function getTemplateRaw($id, $version){
        $file = $this->getFileName($id).".$version";
        return io_readFile($file);
    }

    public function getRaw($id, $rev=NULL){
        return rawWiki($id, $rev);
    }

    public function getRawSlices($id, $range="", $rev=""){
        return rawWikiSlices($range, $id, $rev);
    }

    public function getToc($id){
        global $ACT;
        $act_aux = $ACT;
        $ACT = "show";
        $toc = tpl_toc(TRUE);
        $ACT = $act_aux;
        return $toc;
    }


   private function p_wiki_xhtml($id, $rev='', $excuse=true){
       $file = $this->getFileName($id,$rev);
       $ret  = '';

       //ensure $id is in global $ID (needed for parsing)
       global $ID;
       $keep = $ID;
       $ID   = $id;

       if($rev){
           if(@file_exists($file)){
               $ret = p_render('xhtml',p_get_instructions(io_readWikiPage($file,$id,$rev)),$info); //no caching on old revisions
           }elseif($excuse){
               $ret = WikiIocLangManager::getXhtml('norev');
           }
       }else{
           if(@file_exists($file)){
               $ret = p_cached_output($file,'xhtml',$id);
           }elseif($excuse){
               $ret = WikiIocLangManager::getXhtml('newpage');
           }
       }

       //restore ID (just in case)
       $ID = $keep;

       return $ret;
    }

    public function getInstructions($id, $rev=NULL){
        $file = $this->getFileName($id);
        if(!$rev){
            $instructions = p_cached_instructions($file, FALSE, $id);
        }else{
            $instructions = p_get_instructions(io_readWikiPage($file,$id,$rev));
        }
        return $instructions;
    }

    public function countRevisions($id){
        return IocCommon::countRevisions($id);
    }

    public function getRevisionList($id, $offset = 0){
        $maxAmount = $amount = WikiGlobalConfig::getConf('revision-lines-per-page', 'wikiiocmodel');

        $revisions = getRevisions($id, $offset, $maxAmount + 1 );

        $ret = [];
        $ret['totalamount'] = IocCommon::countRevisions($id);
         if (count($revisions)>$maxAmount) {
             $ret['show_more_button'] = true;
             array_pop($revisions);
             $ta = $offset+$maxAmount;
             //$ret['totalamount'] = "+ de $ta";
        }else{
             $amount = count($revisions);
             $ta = $offset+$amount;
             //$ret['totalamount'] = "$ta";
         }

        foreach ($revisions as $revision) {
            $ret[$revision] = getRevisionInfo($id, $revision);
            $ret[$revision]['date'] =  WikiPageSystemManager::extractDateFromRevision($ret[$revision]['date']);
        }

        $ret['current'] = @filemtime(wikiFN($id));
        $ret['docId'] = $id;
        $ret['position'] = $offset;
        $ret['amount'] = $amount;
        $ret['maxamount'] = $maxAmount;
        $ret['summary'] = getRevisionInfo($id, $ret['current'])['sum'];

        return $ret;
    }
}
