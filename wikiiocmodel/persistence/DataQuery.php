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
     * Canvia el nom de tots els directoris demanats que es trobin a 'data/'
     * @param string $base_old_dir : ruta wiki del directori que canvia de nom
     * @param string $old_name : nom actual del directori
     * @param string $base_new_dir : ruta wiki del nou directori
     * @param string $new_name : nou nom del directori
     * @throws Exception
     */
    public function renameDirNames($base_old_dir, $old_name, $base_new_dir, $new_name) {
        $paths = $this->_arrayDataFolders();

        foreach ($paths as $dir) {
            $basePath = WikiGlobalConfig::getConf($dir);
            $oldPath = "$basePath/$base_old_dir/$old_name";
            if (file_exists($oldPath)) {
                $newPath = "$basePath/$base_new_dir/$new_name";
                if (! rename($oldPath, $newPath) )
                    throw new Exception("renameProjectOrDirectory: Error mentre canviava el nom del projecte/carpeta a $dir.");
            }
        }
    }

    /**
     * Canvia el nom dels arxius que contenen (en el nom) l'antiga ruta del projecte o directori
     * @param string $base_old_dir : directori wiki del projecte o directori
     * @param string $old_name : nom actual del projecte o directori
     * @param string $base_new_dir : directori wiki del projecte o directori
     * @param string $new_name : nou nom del projecte o directori (nom actual)
     * @param array|string $listfiles : llista d'arxius o extensió dels arxius (per defecte ".zip") generats pel render que cal renombrar
     * @throws Exception
     */
    public function renameRenderGeneratedFiles($base_old_dir, $old_name, $base_new_dir, $new_name, $listfiles=["extension","\.zip"], $recursive=FALSE) {
        $newPath = WikiGlobalConfig::getConf('mediadir')."/$base_new_dir/$new_name";

        $ret = $this->_renameRenderGeneratedFiles($newPath, str_replace("/", "_", "$base_old_dir/$old_name"), $old_name, $new_name, $listfiles, $recursive);
        if (is_string($ret)) {
            throw new Exception("renameProjectOrDirectory: Error mentre canviava el nom de l'arxiu $ret.");
        }
    }

    /**
     * Canvia el nombre de los archivos cuyo nombre contiene el antiguo nombre de directorio
     * @param string $path : ruta sencera del sistema al directori 'data/media' de la wiki
     * @param string $base_name : nom base dels arxius
     * @param string $old_name : nom actual del directori
     * @param string $new_name : nou nom del directori
     * @param array $listfiles lista de terminaciones de fichero
     * @return boolean|string TRUE si ha ido bien, "ruta del fichero" si se ha producido error al renombrar
     */
    private function _renameRenderGeneratedFiles($path, $base_name, $old_name, $new_name, $listfiles, $recursive=FALSE) {
        $ret = TRUE;
        $scan = @scandir($path);
        if ($scan) $scan = array_diff($scan, [".", ".."]);
        if ($scan) {
            foreach ($scan as $file) {
                if (is_dir("$path/$file")) {
                    if ($recursive) {
                        $ret = $this->_renameRenderGeneratedFiles("$path/$file", $base_name, $old_name, $new_name, $listfiles, TRUE);
                        if (is_string($ret)) break;
                    }
                }elseif (preg_match("/^$base_name/", $file)) {
                    if (!empty($listfiles)) {
                        for ($i=1; $i<count($listfiles); $i++) {
                            $ext .= $listfiles[$i] ."|";
                        }
                        $ext = substr($ext, 0, -1);
                        if ($listfiles[0] === "fullname") {
                            $search = "/($ext)/";
                        }else {
                            $search = "/{$base_name}.*?($ext)/";
                        }
                        if (preg_match($search, $file)) {
                            $newfile = preg_replace("/(_*?){$old_name}([\.|_])/", "$1{$new_name}$2", $file);
                            $ret = rename("$path/$file", "$path/$newfile");
                            if (!$ret) {
                                $ret = "$path/$file";
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Canvia el contingut dels arxius ".changes" i ".meta" que contenen la ruta del directori per la nova ruta
     * @param string $base_old_dir : directori wiki origen
     * @param string $old_name : nom actual del directori
     * @param string $base_new_dir : directori wiki nou
     * @param string $new_name : nou nom del directori
     * @throws Exception
     */
    public function changeOldPathInRevisionFiles($base_old_dir, $old_name, $base_new_dir, $new_name, $file_sufix=[], $recursive=FALSE) {
        $paths = ['metadir',       /*meta*/
                  'mediametadir',  /*media_meta*/
                  'metaprojectdir' /*project_meta*/
                 ];
        if (empty($file_sufix)) {
            $suffix = FALSE;
        }else {
            array_pop($file_sufix);
            $suffix = "(".implode("|", $file_sufix).")";
        }
        $base_name = str_replace("/", "_", $base_old_dir);
        $list_files = "\.(changes|meta)";
        $ret = TRUE;
        foreach ($paths as $dir) {
            $newPath = WikiGlobalConfig::getConf($dir)."/$base_new_dir/$new_name";
            $ret = $this->_changeOldPathInFiles($newPath, $base_name, $old_name, $new_name, $list_files, $suffix, $recursive);
            if (is_string($ret)) break;
        }
        if (is_string($ret)) {
            throw new Exception("renameProjectOrDirectory: Error mentre canviava el contingut de $ret.");
        }
    }

    /**
     * Afegir nova entrada als arxius .changes que indica que s'ha produït un canvi de nom de directori
     * @param string $ns
     * @param string $base_old_dir : directori wiki que està canviant de nom
     * @param string $old_name : antic nom del directori
     * @param string $base_new_dir : directori wiki que està canviant de nom
     * @param string $new_name : nou nom del directori
     * @throws Exception
     */
    public function addLogEntryInRevisionFiles($ns, $base_old_dir, $old_name, $base_new_dir, $new_name) {
        $paths = ['datadir' /*pages*/, 'olddir' /*attic*/];
        $path = WikiGlobalConfig::getConf($paths[0])."/$base_old_dir/$new_name";
        $attic = WikiGlobalConfig::getConf($paths[1])."/$base_old_dir/$new_name";
        if (@scandir($path)) {
            $ret = $this->_addLogEntryInRevisionFiles($ns, $path, $attic, $old_name, $new_name);
        }
        if (is_string($ret)) {
            throw new Exception("addLogEntryInRevisionFiles: Error mentre afegia nova entrada a l'arxiu .changes de $ret.");
        }
    }

    private function _addLogEntryInRevisionFiles($ns, $path, $attic, $old_name, $new_name) {
        $ret = "";
        $scan = @scandir($path);
        $scan = array_diff($scan, [".", ".."]);
        if ($scan) {
            foreach ($scan as $file) {
                if (is_dir("$path/$file")) {
                    $this->_addLogEntryInRevisionFiles("$ns:$file", "$path/$file", "$attic/$file", $old_name, $new_name);
                }else {
                    $id = "$ns:".str_replace(".txt", "", $file);
                    $summary = "rename old_directory=".str_replace(["$new_name",":"], ["$old_name","."], $ns);
                    $pagelog = new PageChangeLog($id);
                    $oldRev = $pagelog->getRevisions(-1, 1);
                    if (!empty($oldRev)) {
                        $oldRev = $oldRev[0];
                        $last_rev_name = preg_replace("/^(.*)(\..*)$/", "$1.${oldRev}$2.gz", $file);
                    }
                    if (!empty($oldRev) && file("$attic/$last_rev_name")) {
                        $time_rev = time();
                        $new_rev_name = preg_replace("/^(.*)(\..*)$/", "$1.${time_rev}$2.gz", $file);
                        $ret = system("cd $attic; ln -s $last_rev_name $new_rev_name"); //crea enlace simbólico
                        if ($ret === "") {
                            addLogEntry($time_rev, $id, DOKU_CHANGE_TYPE_MINOR_EDIT, $summary);
                        }else {
                            $ret = "$path/$file";
                            break;
                        }
                    }else {
                        //generació forçada d'una revisió
                        $text = file_get_contents("$path/$file")."\n"; //els fitxers han de tenir algún canvi
                        saveWikiText($id, $text, $summary, TRUE);      //sinó no es fa res
                    }
                }
            }
        }
        return ($ret === "");
    }

    /*
    private function _addLogEntryInMediaRevisionFiles($ns, $path, $attic, $old_name, $new_name, $type) {
        global $conf;
        $ret = "";
        $scan = @scandir($path);
        $scan = array_diff($scan, [".", ".."]);
        if ($scan) {
            foreach ($scan as $file) {
                if (is_dir("$path/$file")) {
                    $this->_addLogEntryInMediaRevisionFiles("$ns:$file", "$path/$file", "$attic/$file", $old_name, $new_name, $type);
                }elseif (substr($file, -8) === $type) {
                    $id = "$ns:".str_replace($type, "", $file);
                    $summary = str_replace($new_name, $old_name, $ns);
                    $medialog = new MediaChangeLog($id);
                    $oldRev = $medialog->getRevisions(-1, 1);
                    $oldRev = (int) (empty($oldRev) ? 0 : $oldRev[0]);
                    $last_rev_name = preg_replace("/^(.*)(\..*)(".$type.")$/, $1.{$oldRev}$2", $file);
                    $time_rev = time();
                    $new_rev_name = preg_replace("/^(.*)(\..*)(".$type.")$/", "$1.${time_rev}$2", $file);
                    $ret = system("cd $attic; ln -s $last_rev_name $new_rev_name"); //crea enlace simbólico
                    if ($ret === "") {
                        $logline = array(
                                'date'  => $time_rev,
                                'ip'    => clientIP(true),
                                'type'  => DOKU_CHANGE_TYPE_MINOR_EDIT,
                                'id'    => $id,
                                'user'  => $_SERVER['REMOTE_USER'],
                                'sum'   => "rename old_directory=$summary",
                                'extra' => ''
                                );
                        $logline = implode("\t", $logline)."\n";
                        io_saveFile(metaFN($id, '.changes'), $logline, true); //page changelog
                        io_saveFile($conf['changelog'], $logline, true);      //global changelog cache
                     }else {
                        $ret = "$path/$file";
                        break;
                    }
                }
            }
        }
        return ($ret === "");
    }
    */

    /**
     * Canvia el contingut dels arxius que contenen l'antiga ruta del projecte (normalment la ruta absoluta a les imatges)
     * @param string $base_old_dir : directori wiki del projecte
     * @param string $old_name : nom actual del projecte
     * @param string $base_new_dir : directori wiki del projecte
     * @param string $new_name : nou nom del projecte
     * @throws Exception
     */
    public function changeOldPathInContentFiles($base_old_dir, $old_name, $base_new_dir, $new_name, $file_sufix=FALSE, $recursive=FALSE) {
        $newPath = WikiGlobalConfig::getConf('datadir')."/$base_new_dir/$new_name";
        $suffix = (is_array($file_sufix)) ? "(".implode("|", $file_sufix).")" : FALSE;
        $base_name = str_replace("/", "_", $base_old_dir);
        $ret = $this->_changeOldPathInFiles($newPath, $base_name, $old_name, $new_name, "\.txt$", $suffix, $recursive);
        if (is_string($ret)) {
            throw new Exception("renameProjectOrDirectory: Error mentre canviava el contingut d'algun axiu a $ret.");
        }
    }

    private function _changeOldPathInFiles($path, $base_name, $old_name, $new_name, $list_files, $suffix=FALSE, $recursive=FALSE) {
        $ret = TRUE;
        $scan = @scandir($path);
        $scan = array_diff($scan, [".", ".."]);
        if ($scan) {
            foreach ($scan as $file) {
                if (is_dir("$path/$file")) {
                    if ($recursive) {
                        $ret = $this->_changeOldPathInFiles("$path/$file", $base_name, $old_name, $new_name, $list_files, $suffix, TRUE);
                        if (is_string($ret)) break;
                    }
                }elseif (preg_match("/$list_files/", $file)) {
                    if (($content = file_get_contents("$path/$file"))) {
                        $c = $c2 = 0;
                        $content = preg_replace("/(:)?\b$old_name((:|\t|\"))?/m", "$1{$new_name}$2", $content, -1, $c);
                        if ($suffix) {
                            if (preg_match("/{$base_name}_{$old_name}/", $content)) {
                                $content = preg_replace("/({$base_name}_)($old_name)(_*?.*?)($suffix)/", "$1{$new_name}$3$4", $content, -1, $c2);
                                $c += $c2;
                            }
                        }
                        if ($c > 0) {
                            $ret = file_put_contents("$path/$file", $content, LOCK_EX);
                            if (!$ret) {
                                $ret = "$path/$file";
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * Canvia el contingut de l'arxiu ACL que pot contenir la ruta antiga del projecte
     * @param string $old_name : nom actual del projecte
     * @param string $new_name : nou nom del projecte
     * @throws Exception
     */
    public function changeOldPathInACLFile($base_old_dir, $old_name, $base_new_dir, $new_name) {
        $file = DOKU_CONF."acl.auth.php";
        if (($content = file_get_contents($file))) {
            $old_ns = str_replace("/", ":", $base_old_dir);
            $new_ns = str_replace("/", ":", $base_new_dir);
            $content = preg_replace("/$old_ns:$old_name:/m", "$new_ns:$new_name:", $content);
            if (file_put_contents($file, $content, LOCK_EX) === FALSE)
                throw new Exception("renameProjectOrDirectory: Error mentre canviava el nom del projecte/directori a $file.");
        }
    }

    /**
     * Llista de directoris en 'data'
     * @return array
     */
    protected function _arrayDataFolders() {
        return ['datadir',       /*pages*/
                'olddir',        /*attic*/
                'mediadir',      /*media*/
                'mediaolddir',   /*media_attic*/
                'metadir',       /*meta*/
                'mediametadir',  /*media_meta*/
                'mdprojects',    /*mdprojects*/
                'revisionprojectdir', /*project_attic*/
                'metaprojectdir',  /*project_meta*/
               ];
    }

    /**
     * Duplica tots els directoris demanats que es trobin a 'data/'
     * @param string $base_dir : ruta wiki del directori destí de la còpia
     * @param string $new_name : nom del projecte duplicat
     * @param string $old_path : ruta wiki del directori original
     * @param string $old_name : nom del projecte original
     * @throws Exception
     */
    public function duplicateDirNames($base_dir, $new_name, $old_path, $old_name) {
        $paths = $this->_arrayDataFolders();

        foreach ($paths as $dir) {
            $basePath = WikiGlobalConfig::getConf($dir);
            $oldPath = "$basePath/$old_path/$old_name";
            if (file_exists($oldPath)) {
                $newPath = "$basePath/$base_dir/$new_name";
                if (!$this->_recurse_copy($oldPath, $newPath) )
                    throw new Exception("duplicateProject: Error mentre duplicava el projecte a $dir.");
            }
        }
    }

    private function _recurse_copy($src, $dst) {
        $dir = opendir($src);
        $ret = mkdir($dst, 0775, TRUE);
        if (!$ret)
            throw new Exception("duplicateProject: Error mentre duplicava el projecte. Error de creació del directori: $dst.");
        while(false !== ($file = readdir($dir))) {
            if ($file != "." && $file != "..") {
                if (is_dir("$src/$file") ) {
                    $ret |= $this->_recurse_copy("$src/$file", "$dst/$file");
                }
                else {
                    $ret |= copy("$src/$file", "$dst/$file");
                }
            }
        }
        closedir($dir);
        return $ret;
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
        $ret[self::K_TYPE] = is_dir($page) ? "d" : (page_exists($ns) ? "f" : "");

        if ($ns) {
            $camins = explode(":", $ns);
            $page .= implode("/", $camins);
            $type = $ret[self::K_TYPE];
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
        global $plugin_controller;

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
                    break;
                }
            }
        }
        if ($ret[self::K_TYPE] === "p") {
            $file = $plugin_controller->getProjectTypeDir($dirName)."metadata/config/nsTreeTypes.json";
            if (is_file($file)) {
                if (!empty($nsTreeTypes = file_get_contents($file))) {
                    $ret[self::K_TYPE] = json_decode($nsTreeTypes, TRUE)['main'];
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
