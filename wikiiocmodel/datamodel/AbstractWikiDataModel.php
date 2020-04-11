<?php
/**
 * Description of AbstractWikiDataModel
 * @author josep
 */
abstract class AbstractWikiDataModel extends AbstractWikiModel{

    protected $pageDataQuery;
    protected $projectMetaDataQuery;

    public function __construct($persistenceEngine) {
        parent::__construct($persistenceEngine);
        $this->projectMetaDataQuery = $persistenceEngine->createProjectMetaDataQuery();
        $this->pageDataQuery = $persistenceEngine->createPageDataQuery();
    }

    public function getPersistenceEngine(){
        return $this->persistenceEngine;
    }

    public function getProjectMetaDataQuery() {
        return $this->projectMetaDataQuery;
    }

    public function getPageDataQuery() {
        return $this->pageDataQuery;
    }

    public function getThisProject($id) {
        return $this->getPageDataQuery()->getThisProject($id);
    }

    public function haveADirProject($id) {
        return $this->getPageDataQuery()->haveADirProject($id);
    }

    /**
     * Valida que exista el nombre de usuario que se desea utilizar (pueden ser varios nombres)
     */
    public function validaNom($nom) {
        global $auth;
        $aNoms = preg_split("/[\s,]+/", $nom);
        if (!empty($aNoms)) {
            $ret = TRUE;
            foreach ($aNoms as $n) {
                $ret &= ($auth->getUserCount(['user' => $n]) > 0);
            }
        }
        return $ret;
    }

    public function createDataDir($id) {
        $this->getProjectMetaDataQuery()->createDataDir($id);
    }

    public function createFolder($new_folder){
        return $this->getProjectMetaDataQuery()->createFolder(str_replace(":", "/", $new_folder));
    }

    public function folderExists($ns) {
        $id = str_replace(":", "/", $ns);
        return file_exists($id) && is_dir($id);
    }

    public function fileExistsInProject($id, $file) {
        $ns = str_replace(":", "/", $id);
        $fileList = $this->getPageDataQuery()->getFileList($ns);
        if ($fileList) {
            $ret = in_array($file, $fileList);
        }
        return $ret;
    }

    public function getListProjectTypes($all=FALSE) {
        return $this->getProjectMetaDataQuery()->getListProjectTypes($all);
    }

    public function getListTemplateDirFiles($nsDirTemplates) {
        return $this->getProjectMetaDataQuery()->getListTemplateDirFiles($nsDirTemplates);
    }

    public function getListMetaDataComponentTypes($metaDataPrincipal, $component) {
        return $this->getProjectMetaDataQuery()->getListMetaDataComponentTypes($metaDataPrincipal, $component);
    }

    // Crida principal de la comanda ns_tree_rest
    public function getNsTree($currentnode, $sortBy, $onlyDirs=FALSE, $expandProject=FALSE, $hiddenProjects=FALSE, $fromRoot=FALSE, $subSetList=NULL) {
        return $this->pageDataQuery->getNsTree($currentnode, $sortBy, $onlyDirs, $expandProject, $hiddenProjects, $fromRoot, $subSetList);
    }

    public function getNsTreeSubSetsList($ns) {
        $prps = $this->getPageDataQuery()->isAProject($ns, TRUE);
        if ($prps[ProjectKeys::KEY_TYPE] === "p" || $prps[ProjectKeys::KEY_TYPE] === "pd") {
            $subSets = $this->getProjectMetaDataQuery()->getListMetaDataSubSets($prps[ProjectKeys::KEY_PROJECT_TYPE]);
            foreach ($subSets as $subset) {
                if ($subset !== ProjectKeys::VAL_DEFAULTSUBSET) {
                    $subSetList[] = [ProjectKeys::KEY_ID => $ns,
                                     ProjectKeys::KEY_NAME => $subset,
                                     ProjectKeys::KEY_TYPE => "s",
                                     ProjectKeys::KEY_NSPROJECT => $prps[ProjectKeys::KEY_NSPROJECT],
                                     ProjectKeys::KEY_PROJECT_TYPE => $prps[ProjectKeys::KEY_PROJECT_TYPE],
                                     ProjectKeys::KEY_METADATA_SUBSET => $subset
                                    ];
                }
            }
        }
        return $subSetList;
    }

}
