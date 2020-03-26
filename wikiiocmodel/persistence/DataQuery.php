<?php
/**
 * Description of DataQuery
 * @author josep
 */
if (! defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');

require_once (DOKU_INC . 'inc/pageutils.php');
require_once (DOKU_INC . 'inc/common.php');
require_once (DOKU_INC . 'inc/io.php');

abstract class DataQuery {
    const K_PROJECTTYPE       = ProjectKeys::KEY_PROJECT_TYPE;
    const K_PROJECTSOURCETYPE = ProjectKeys::PROJECT_SOURCE_TYPE;
    const K_PROJECTOWNER      = ProjectKeys::PROJECT_OWNER;
    const K_ID        = ProjectKeys::KEY_ID;
    const K_NS        = ProjectKeys::KEY_NS;
    const K_NAME      = ProjectKeys::KEY_NAME;
    const K_NSPROJECT = ProjectKeys::KEY_NSPROJECT;
    const K_TYPE      = ProjectKeys::KEY_TYPE;

    private $datadir;
    private $metaDataPath;
    private $metaDataExtension;

    private function setBaseDir($base=NULL) {
        if (!isset($this->datadir)) {
            $this->datadir = ($base) ? $base : WikiGlobalConfig::getConf('datadir');
            $this->metaDataPath = WikiGlobalConfig::getConf('mdprojects');
            $this->metaDataExtension = WikiGlobalConfig::getConf('mdextension');
        }
    }

    public abstract function getFileName($id, $especParams=NULL);

    public abstract function getNsTree($currentNode, $sortBy, $onlyDirs=FALSE, $expandProject=FALSE, $hiddenProjects=FALSE, $root=FALSE);

    /**
     * Busca si la ruta (id) contiene un directorio de proyecto
     * @param string 'id'
     * @return boolean
     */
    public function haveADirProject($id) {
        $this->setBaseDir();
        $ret = $this->getNsItems($id);
        return isset($ret[self::K_PROJECTTYPE]);
    }

    /**
     * Busca si la ruta (ns) es un proyecto
     * @param string $ns
     * @return boolean
     */
    public function isAProject($ns, $full=FALSE) {
        $ret = $this->getNsProperties($ns);
        if ($full)
            return $ret;
        else
            return isset($ret[self::K_PROJECTTYPE]);
    }

    /**
     * Busca, de profundo a superfície, si en la ruta ns hay un proyecto
     * @param string $ns
     * @return array[type, projectType, ns] del primer proyecto obtenido
     */
    public function getThisProject($ns) {
        $this->setBaseDir();
        $ret = $this->getParentProjectProperties(explode(":", $ns));
        return $ret;
    }

    /**
     * Retorna la llista de fitxers continguts a l'espai de noms identificat per $ns
     * @param string $ns és l'espai de noms d'on consultar la llista
     * @return array amb la llista de fitxers
     */
    public function getFileList($ns) {
        $this->setBaseDir();
        $arrayDir = scandir("{$this->datadir}/$ns");
        if ( $arrayDir ) {
            unset( $arrayDir[0] );
            unset( $arrayDir[1] );
            $arrayDir = array_values( $arrayDir );
        } else {
            $arrayDir = array();
        }
        return $arrayDir;
    }

    public function createFolder($new_folder){
        $this->setBaseDir();
        return mkdir("{$this->datadir}/$new_folder");
    }

    /**
     * Retorna l'espai de noms que conté el fitxer identificat per $id
     * @param string $id és l'identificador del fitxer d'on extreu l'espai de noms
     * @return string amb l'espai de noms extret
     */
    public function getNs($id){
        return getNS($id);
    }

    /**
     * Retorna el nom simple (sense l'espais de noms) del fitxer o directori identificat per $id
     * @param string $id
     * @return string contenint el nom simple del fitxer o directori
     */
    public function getIdWithoutNs($id){
        return noNS($id);
    }

    public function resolve_id($ns,$id,$clean=true){
        resolve_id($ns, $id, $clean);
    }

    /**
    * Crea el directori on ubicar el fitxer referenciat per $filePath després
    * d'extreure'n el nom del fitxer. Aquesta funció no crea directoris recursivamnent.
    */
    public function makeFileDir( $filePath ) {
        io_makeFileDir( $filePath );
    }

    /**
     * Mètode privat que obté l'arbre de directoris a partir d'un espai de noms
     * i el sistema de dades concret d'on obtenir-lo (media, data, meta, etc)
     * mlozan54: també retorna si el directori és un projecte o el directori o fitxer és a dins d'un projecte
     *      Node                        Tipus de retorn
     *      Directori                      d
     *      Fitxer                         f
     *      Projecte                       p
     *      Directori dins de projecte     pd
     *      Fitxer dins de projecte        pf
     * @param string $base
     * @param string $currentnode (ruta en formato wiki)
     * @param integer $sortBy [0|1]
     * @param boolean $onlyDirs
     * @param boolean $expandProject
     * @param boolean $hiddenProjects
     * @param string $root
     * @return json conteniendo el nodo actual con sus propiedades y sus hijos, con sus propiedades, a 1 nivel de profundidad
     */
    protected function getNsTreeFromGenericSearch( $base, $currentnode, $sortBy, $onlyDirs=FALSE, $function='search_index', $expandProject=FALSE, $hiddenProjects=FALSE, $root=FALSE, $subSetList=NULL ) {
        $this->setBaseDir($base);
        $nodeData    = array();
        $children    = array();
        $sortOptions = array(self::K_NAME, 'date');    //no se usa

        if ( $currentnode == "_" ) {
            $path = $base.'/'.($root ? "$root/" : "");
            $path = str_replace(':', '/', $path);
            $name = ($root) ? $root : "";
            if (is_dir($path)){
                $itemsProject = $this->getNsItems($root);
                if ($root && $itemsProject[self::K_PROJECTTYPE])
                    $itemsProject = $this->updateNsProperties($root, $itemsProject);
                $type = $itemsProject[self::K_TYPE];
            }else{
                $itemsProject = $this->getNsItems($root);
                $type = $itemsProject[self::K_TYPE];
            }
            $ret = array(
                      self::K_ID => $name,
                      self::K_NAME => $name,
                      self::K_TYPE => $type
                   );
            if ($itemsProject[self::K_PROJECTTYPE]) {
                $ret[self::K_PROJECTTYPE] = $itemsProject[self::K_PROJECTTYPE];
            }
            if ($itemsProject[self::K_PROJECTSOURCETYPE]) {
                $ret[self::K_PROJECTSOURCETYPE] = $itemsProject[self::K_PROJECTSOURCETYPE];
                $ret[self::K_PROJECTOWNER]      = $itemsProject[self::K_PROJECTOWNER];
            }

            return $ret;
        }

        if ( $currentnode ) {
            $node  = $currentnode;
            $aname = explode(":", $node);
            $level = count($aname);
            $name  = $aname[$level - 1]; //ns (espacio de nombres, es decir, padre)
        } else {
            $node  = ($root) ? $root : "";
            $aname = explode( ":", $node );
            $level = ($root) ? count($aname) : 0;
            $name  = ($root) ? $root : "";
        }
        $sort = $sortOptions[$sortBy];  //no se usa

        $opts = array(self::K_NS => $node);
        if ($function == 'search_universal') {
            global $conf;
            $opts = array(
                self::K_NS => $node,
                'listdirs' => true,
                'listfiles' => true,
                'sneakyacl' => $conf['sneaky_index']
            );
        }
        $dir = str_replace(':', '/', $node);
        search($nodeData, $base, $function, $opts, $dir, $level);

        $propertiesNs = $this->getNsProperties($node);
        $itemsProject = $this->updateNsProperties($node, $propertiesNs);

        if ($itemsProject[self::K_PROJECTTYPE] || $itemsProject[self::K_TYPE] === "pd") {
            if ($expandProject) {
                $children = $this->fillProjectNode($nodeData, $level, $itemsProject, $onlyDirs, $subSetList);
            }
        }elseif ($nodeData) {
            $children = $this->fillNode($nodeData, $level, $onlyDirs, $hiddenProjects);
        }

        $tree = array(
                   self::K_ID   => $node,
                   self::K_NAME => $name,
                   self::K_TYPE => $itemsProject[self::K_TYPE]
                );
        if ($itemsProject[self::K_PROJECTTYPE]) {
            $tree[self::K_PROJECTTYPE] = $itemsProject[self::K_PROJECTTYPE];
            $tree[self::K_NSPROJECT]   = $itemsProject[self::K_NSPROJECT];
        }
        if ($itemsProject[self::K_PROJECTSOURCETYPE]) {
            $tree[self::K_PROJECTSOURCETYPE] = $itemsProject[self::K_PROJECTSOURCETYPE];
            $tree[self::K_PROJECTOWNER]   = $itemsProject[self::K_PROJECTOWNER];
        }
        $tree['children'] = $children;
        //Logger::debug("getNsTreeFromGenericSearch: \$params=".json_encode(array('base'=>$base,'currentnode'=>$currentnode,'sortBy'=>$sortBy,'onlyDirs'=>$onlyDirs,'function'=>$function,'expandProject'=>$expandProject,'hiddenProjects'=>$hiddenProjects,'root'=>$root))."\n".
                        //"\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\t\$tree=".json_encode($tree)."\n".
                        //"\$tree=".print_r($tree, TRUE), 0, __LINE__, "DataQuery", -1, TRUE);
        return $tree;
    }

    /**
     * Pone atributos a los hijos incluidos en $nodeData
     * @param array $nodeData lista del primer nivel de directorios y ficheros
     *              $nodeData = [$id, $ns=ns del padre, $perm, $type=[d|f], $level>=1, open]
     * @param integer $level
     * @param array $itemsProject
     * @param boolean $onlyDirs
     * @return array con todos los hijos incluidos en $nodeData con sus propiedades
     */
    private function fillProjectNode($nodeData, $level, $itemsProject, $onlyDirs, $subSetList=NULL) {
        //Logger::debug("fillProjectNode->nodeData: ".json_encode($nodeData), 0, __LINE__, "DataQuery", -1, TRUE);
        //Logger::debug("fillProjectNode->itmsProject: ".json_encode($itemsProject), 0, __LINE__, "DataQuery", -1, TRUE);
        $c = ($subSetList && count($subSetList) > 0) ? count($subSetList) : 0; //countSubSets
        $children = ($c > 0) ? $subSetList : array();

        foreach (array_keys($nodeData) as $item) {

            if ($onlyDirs && $nodeData[$item][self::K_TYPE] == "d" || !$onlyDirs) {
                $itemc = $item+$c;
                $children[$itemc][self::K_ID] = $nodeData[$item][self::K_ID];
                $children[$itemc][self::K_NAME] = explode(":", $nodeData[$item][self::K_ID])[$level];

                $_type = $nodeData[$item][self::K_TYPE];
                if ($_type === "d") {
                    $propertiesNs = $this->getNsProperties($nodeData[$item][self::K_ID]);
                    $_type = ($propertiesNs[self::K_PROJECTTYPE]) ? "o" : $propertiesNs[self::K_TYPE];
                }
                if (isset($propertiesNs) && $propertiesNs[self::K_PROJECTTYPE]) {
                    $children[$itemc][self::K_PROJECTTYPE] = $propertiesNs[self::K_PROJECTTYPE];
                    $children[$itemc][self::K_NSPROJECT] = $propertiesNs[self::K_NSPROJECT];
                }else {
                    $children[$itemc][self::K_PROJECTTYPE] = $itemsProject[self::K_PROJECTTYPE];
                    $children[$itemc][self::K_NSPROJECT] = $itemsProject[self::K_NSPROJECT];
                }
                unset($propertiesNs);
                $children[$itemc][self::K_TYPE] = "p$_type";
            }
        }
        return $children;
    }

    /**
     * Pone atributos a los hijos incluidos en $nodeData
     * @param array $nodeData lista del primer nivel de directorios y ficheros [id, ns, perm, type, level, open]
     * @param int $level
     * @param bool $onlyDirs
     * @param bool $hiddenProjects
     * @return array lista de hijos con sus atributos
     */
    private function fillNode($nodeData, $level, $onlyDirs, $hiddenProjects) {
        $children = array();

        foreach (array_keys($nodeData) as $item) {

            if ($onlyDirs && $nodeData[$item][self::K_TYPE] == "d" || !$onlyDirs) {

                if ($nodeData[$item][self::K_TYPE] == "d") {
                    $itemsProject = $this->getNsItems($nodeData[$item][self::K_ID]);
                    $isProject = ($itemsProject[self::K_PROJECTTYPE] !== NULL);

                    if (!$isProject || $hiddenProjects == FALSE) {
                        $children[$item][self::K_ID] = $nodeData[$item][self::K_ID];
                        $children[$item][self::K_NAME] = explode(":", $nodeData[$item][self::K_ID])[$level];
                        $children[$item][self::K_TYPE] = $itemsProject[self::K_TYPE];
                    }

                    if ($isProject && $hiddenProjects == FALSE) {
                        $children[$item][self::K_PROJECTTYPE] = $itemsProject[self::K_PROJECTTYPE];
                        $children[$item][self::K_NSPROJECT] = $itemsProject[self::K_NSPROJECT];
                    }

                } else {
                    $children[$item][self::K_ID] = $nodeData[$item][self::K_ID];
                    $children[$item][self::K_NAME] = explode(":", $nodeData[$item][self::K_ID])[$level];
                    $children[$item][self::K_TYPE] = $nodeData[$item][self::K_TYPE];
                }
            }
        }
        array_unshift($children,"noname");  //Se usa para renumerar desde 0 las claves del array
        array_shift($children);             //que se desmelenan al excluir los directorios de proyectos
        return $children;
    }

    /**
     * Evalua que tipo de elemento es la ruta $ns y retorna las propiedades que le son propias
     * @param type $ns : ns (ruta wiki relativa a pages) que se evalúa
     * @return array : propiedades del elemento $ns
     */
    private function getNsItems($ns) { //debería llamarse getFullNsProperties()
        $this->setBaseDir();
        $page = $this->datadir."/";
        $camins = ($ns) ? explode(":", $ns) : NULL;
        if ($camins)
            $page .= implode("/", $camins);
        $ret[self::K_TYPE] = is_dir($page) ? "d" : (page_exists($ns) ? "f" : "");
        $type = $ret[self::K_TYPE];

        if ($ns) {
            $pathElement = $this->metaDataPath."/".str_replace(":", "/", $ns);

            while ($camins) {
                $nsElement = implode(":", $camins);
                $parentDir = $this->metaDataPath."/".implode("/", $camins);
                if (is_dir($parentDir)) {
                    $fh = opendir($parentDir);
                    while ($current = readdir($fh)) {
                        $currentDir = "$parentDir/$current";
                        if (is_dir($currentDir) && $current !== "." && $current !== "..") {
                            $ret = $this->getProjectProperties($pathElement, $currentDir, $nsElement, $current);
                            if ($ret[self::K_PROJECTTYPE]) {
                                if ($type==="f") {
                                    $ret[self::K_TYPE] = "pf";
                                    $ret[self::K_PROJECTSOURCETYPE] = $ret[self::K_PROJECTTYPE];
                                    $ret[self::K_PROJECTOWNER] = $ret[self::K_NSPROJECT];
                                    unset($ret[self::K_PROJECTTYPE]);
                                    unset($ret[self::K_NSPROJECT]);
                                }
                                return $ret;
                            }
                        }
                    }
                }
                array_pop($camins);
            }
        }
        //Logger::debug("getNsItems: \$ns=$ns, \$ret=".json_encode($ret), 0, __LINE__, "DataQuery", -1, TRUE);
        return $ret;
    }

    /**
     * Busca averiguar si $currentDir es un directorio de proyecto, es decir, si contiene los ficheros de proyecto
     * @param string $pathElement : nombre original del elemento/archivo que se examina (con ruta en mdprojects)
     * @param string $currentDir : ruta absoluta al directorio que se desea explorar para averiguar si contiene el fichero de proyecto
     * @param string $nsElement : ruta absoluta al padre del directorio $currentDir
     * @param string $dirName : nombre del directorio $currentDir
     * @return array con atributos del proyecto
     */
    private function getProjectProperties($pathElement, $currentDir, $nsElement, $dirName) {
        $ret[self::K_TYPE] = is_dir($currentDir) ? "d" : "f";
        $fh = opendir($currentDir);

        while ($currentOne = readdir($fh)) {
            //busca el archivo *.mdpr ($this->metaDataExtension)
            if (!is_dir("$currentDir/$currentOne")) {
                $fileTokens = explode(".", $currentOne);
                if ($fileTokens[sizeof($fileTokens) - 1] === $this->metaDataExtension) {
                    $ret[self::K_TYPE] = "p" . (("$pathElement/$dirName" === $currentDir) ? "" : $ret[self::K_TYPE]);
                    $ret[self::K_PROJECTTYPE] = $dirName;
                    $ret[self::K_NSPROJECT] = $nsElement;
                    return $ret;
                }
            }
        }
        return $ret;
    }

    /**
     * Obtiene el tipo y, en su caso, propiedaddes del padre, en la ruta correspondiente a un ns
     * @return array | null
     */
    private function getNsProperties($ns) {
        $ret[self::K_TYPE] = "";
        if ($ns) {
            $this->setBaseDir();
            $nsPath = str_replace(":", "/", $ns);

            if (is_dir($this->datadir."/$nsPath")) {
                $ret[self::K_TYPE] = "d";
                $ret2 = $this->getParentProjectProperties(explode(":", "$ns:dummy"), "d");
            }
            else if (page_exists($ns)) {
                $ret[self::K_TYPE] = "f";
                $ret2 = $this->getParentProjectProperties(explode(":", $ns), "f");
            }

            if ($ret2) {
                $ret2[self::K_TYPE] .= $ret[self::K_TYPE];
                $ret = $ret2;
            }
        }
        return $ret;
    }

    /**
     * Busca el proyecto padre en la ruta correspondiente a un ns
     * @param array $camins : ns en formato array
     * @return array | null
     */
    private function getParentProjectProperties($camins, $type="d") {
        if (is_array($camins)) {
            $ns_elem = "";
            array_pop($camins); //empezamos justo en el directorio superior

            while ($camins) {
                $ns_elem = implode(":", $camins);
                $projectPath = $this->metaDataPath."/".implode("/", $camins);
                if (is_dir($projectPath)) {
                    $fh = opendir($projectPath);
                    while ($dir_elem = readdir($fh)) {
                        if (is_dir("$projectPath/$dir_elem") && $dir_elem!=="." && $dir_elem!=="..") {
                            $ret = $this->getProjectProperties2("$projectPath/$dir_elem", $ns_elem, $dir_elem, $type);
                            if ($ret[self::K_PROJECTTYPE] || $ret[self::K_PROJECTOWNER]) {
                                return $ret;
                            }
                        }
                    }
                }
                array_pop($camins);
            }
        }
        return $ret;
    }

    private function updateNsProperties($ns, $nsProp) {
        $ret = $this->getParentProjectProperties(explode(":", $ns));
        if ($ret[self::K_PROJECTTYPE]) {
            $type = ($nsProp[self::K_TYPE] === "p") ? "o" : $nsProp[self::K_TYPE];
            $nsProp[self::K_TYPE] = "p$type";
        }
        return $nsProp;
    }

    /**
     * Busca averiguar si $currentDir es un directorio de proyecto, es decir, si contiene los ficheros de proyecto
     * @param string $currentDir : ruta absoluta al directorio que se desea explorar para averiguar si contiene el fichero de proyecto
     * @param string $nsElement : ns del padre del directorio $currentDir
     * @param string $dirName : nombre del directorio $currentDir
     * @return array con atributos del proyecto
     */
    private function getProjectProperties2($currentDir, $nsElement, $dirName, $type="d") {
        $fh = opendir($currentDir);
        while ($currentOne = readdir($fh)) {
            //busca el archivo *.mdpr ($this->metaDataExtension)
            if (!is_dir("$currentDir/$currentOne")) {
                $fileTokens = explode(".", $currentOne);
                if ($fileTokens[sizeof($fileTokens) - 1] === $this->metaDataExtension) {
                    if ($type==="f") {
                        $ret[self::K_TYPE] = "";
                        $ret[self::K_PROJECTSOURCETYPE] = $dirName;
                        $ret[self::K_PROJECTOWNER] = $nsElement;
                    }else {
                        $ret[self::K_TYPE] = "p";
                        $ret[self::K_PROJECTTYPE] = $dirName;
                        $ret[self::K_NSPROJECT] = $nsElement;
                    }
                    return $ret;
                }
            }
        }
        return $ret;
    }

}
