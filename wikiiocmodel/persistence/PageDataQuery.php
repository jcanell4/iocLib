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

    /**
     * Proceso de guardar un fichero de texto wiki. Incluye la generación de los logs .changes y .meta
     * @param string $id : wiki ruta del document
     * @param string $text : contingut del document
     * @param string $summary
     * @param boolean $minor
     * @param boolean $forceSave
     * @param integer $version : número de la versió del document
     */
    public function save($id, $text, $summary, $minor=false, $forceSave=false, $version=NULL){
        global $plugin_controller;
        $filename = array_pop(explode(":", $id));
        // Incluimos la versión de template actual (o la perteneciente a la reversión si viene por parámetro)
        $projectSourceType = $plugin_controller->getProjectSourceType();
        if ($projectSourceType) {
            $projectOwner = $plugin_controller->getProjectOwner();
            $metaDataQuery = $plugin_controller->getPersistenceEngine()->createProjectMetaDataQuery($projectOwner, "main", $projectSourceType);
            if (!$version) {
                $version = $metaDataQuery->getMetaDataAnyAttr("versions")['templates'][$filename];
            }
        }
        if ($version) {
            $summary .= ' {"'.$filename.'":'.$version.'}';
        }

        if (file($id)) $fdt = @filemtime(wikiFN($id));
        saveWikiText($id, $text, $summary, $minor);
        if ($forceSave && $fdt && $fdt === filemtime(wikiFN($id))){
            saveWikiText($id, " ", "");
            saveWikiText($id, $text, $summary, $minor);
        }
        $partialDisabled = strpos($text, '~~USE:WIOCCL~~') !== false;

        $meta['partialDisabled'] = $partialDisabled;
        p_set_metadata($id, $meta);

        // Si es una reversión de un archivo de proyecto, revertimos la versión en el archivo _wikiiocSystem.mdpr_ del proyecto
        if ($projectSourceType) {
            $metaDataQuery->setProjectSystemSubSetVersion($filename, $version);
        }
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

        $pagelog = new PageChangeLog($id);
        $revisions = $pagelog->getRevisions($offset, $maxAmount + 1 );

        $ret = [];
        $ret['totalamount'] = count($revisions);
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
            $ret[$revision] = $pagelog->getRevisionInfo($revision);
            $ret[$revision]['date'] =  WikiPageSystemManager::extractDateFromRevision($ret[$revision]['date']);
        }

        $ret['current'] = @filemtime(wikiFN($id));
        $ret['docId'] = $id;
        $ret['position'] = $offset;
        $ret['amount'] = $amount;
        $ret['maxamount'] = $maxAmount;
        $ret['summary'] = $pagelog->getRevisionInfo($ret['current'])['sum'];

        return $ret;
    }

}
