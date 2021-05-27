<?php
/**
 * Description of ResultsWithFiles
 *
 * @author josep
 */
class ResultsWithFiles {

    //MULTI
    public static function get_html_metadata($result){
        $ext = $result["ext"] ? $result["ext"] : ".zip";
        if ($result['error']) {
            throw new Exception ("Error");
        }
        elseif($result["fileNames"]) {
            if (!$result["dest"]) {
                if (!self::copyFiles($result)) {
                    throw new Exception("Error en la còpia dels arxius d 'esportació des de la ubicació temporal");
                }
            }
            $ret = self::_getHtmlMetadataMultiFile($result);
        }
        else {
            if ($result["zipFile"]){
                $ext = ".zip";
                if (!self::copyFile($result, "zipFile", "zipName")) {
                    throw new Exception("Error en la còpia de l'arxiu zip des de la ubicació temporal");
                }
            }elseif($result["pdfFile"]){
                $ext = ".pdf";
                if (!self::copyFile($result, "pdfFile", "pdfName")) {
                    throw new Exception("Error en la còpia de l'arxiu PDF des de la ubicació temporal");
                }
            }
            $file = WikiGlobalConfig::getConf('mediadir').'/'. preg_replace('/:/', '/', $result['ns']) .'/'.preg_replace('/:/', '_', $result['ns']);
            $ret = self::_getHtmlMetadataFile($result['ns'], $file, $ext);
        }
        return $ret;
    }

    private static function _getHtmlMetadataMultiFile($result) {
        $P = ""; $nP = "";
        $ret = '<span id="exportacio" style="word-wrap: break-word;">';
        for ($i=0; $i<count($result["fileNames"]); $i++){
            $filename = $result["fileNames"][$i];
            $ext = substr($filename, -3);
            $class = "mf_$ext";
            if (isset($result["dest"][$i]) && @file_exists($result["dest"][$i])) {
                $media_path = "lib/exe/fetch.php?media={$result['ns']}:$filename";
                $data = date("d/m/Y H:i:s", filemtime($result["dest"][$i]));

                $ret.= $P.'<a class="media mediafile '.$class.'" href="'.$media_path.'" target="_blank">'.$filename.'</a> ';
                $ret.= '<span style="white-space: nowrap;">'.$data.'</span>'.$nP;
            }else{
                $mode = ($ext==="zip") ? "HTML" : "PDF";
                $ret.= $P.'<span class="media mediafile '.$class.'">No hi ha cap exportació '.$mode.' feta del fitxer '.$result["fileNames"][$i].'</span>'.$nP;
            }
            $P = "<p>"; $nP = "</p>";
        }
        $ret.= '</span>';
        return $ret;
    }

    private static function copyFiles(&$result){
        $result["dest"]=array();
        $ok=false;
        $dest = preg_replace('/:/', '/', $result['ns']);
        $path_dest = WikiGlobalConfig::getConf('mediadir').'/'.$dest;
        if (!file_exists($path_dest)){
            mkdir($path_dest, 0755, TRUE);
        }
        if(is_array($result["files"])){
            $ok=true;
            for($i=0; $i<count($result["files"]); $i++) {
                $ok = $ok && copy($result["files"][$i], $path_dest.'/'.$result["fileNames"][$i]);
                $result["dest"][$i]=$path_dest.'/'.$result["fileNames"][$i];
            }
        }
        return $ok;
    }

    private static function _getHtmlMetadataFile($ns, $file, $ext) {
        if ($ext === ".zip") {
            $P = ""; $nP = "";
            $class = "mf_zip";
            $mode = "HTML";
        }else {
            $P = "<p>"; $nP = "</p>";
            $class = "mf_pdf";
            $mode = "PDF";
        }
        if (@file_exists($file.$ext)) {
            $filename = str_replace(':','_',basename($ns)).$ext;
            $media_path = "lib/exe/fetch.php?media=$ns:$filename";
            $data = date("d/m/Y H:i:s", filemtime($file.$ext));
            $ret.= $P.'<span id="exportacio" style="word-wrap: break-word;">';
            $ret.= '<a class="media mediafile '.$class.'" href="'.$media_path.'" target="_blank">'.$filename.'</a> ';
            $ret.= '<span style="white-space: nowrap;">'.$data.'</span>';
            $ret.= '</span>'.$nP;
        }else{
            $ret.= '<span id="exportacio">';
            $ret.= '<p class="media mediafile '.$class.'">No hi ha cap exportació '.$mode.' feta</p>';
            $ret.= '</span>';
        }
        return $ret;
    }

    private static function copyFile($result, $keyFile, $keyName){
        $dest = preg_replace('/:/', '/', $result['ns']);
        $path_dest = WikiGlobalConfig::getConf('mediadir').'/'.$dest;
        if (!file_exists($path_dest)){
            mkdir($path_dest, 0755, TRUE);
        }
        $ok = copy($result[$keyFile], $path_dest.'/'.$result[$keyName]);
        return $ok;
    }

    /**
     * Remove specified dir
     * @param string $directory
     */
    public static function removeDir($directory) {
        return IocCommon::removeDir($directory);
        /*
        if (!file_exists($directory) || !is_dir($directory) || !is_readable($directory)) {
            return FALSE;
        }else {
            $dh = opendir($directory);
            while ($contents = readdir($dh)) {
                if ($contents != '.' && $contents != '..') {
                    $path = "$directory/$contents";
                    if (is_dir($path)) {
                        self::removeDir($path);
                    }else {
                        unlink($path);
                    }
                }
            }
            closedir($dh);

            if (file_exists($directory)) {
                if (!rmdir($directory)) {
                    return FALSE;
                }
            }
            return TRUE;
        }
         */
    }

}