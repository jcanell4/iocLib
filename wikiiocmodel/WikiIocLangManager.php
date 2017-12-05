<?php
/**
 * Description of WikiIocLangManager
 * @author josep
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define("DOKU_PLUGIN", DOKU_INC."lib/plugins/");
//Está pendiente el establecimiento del valor para el directorio 'wikiiocmodel'
//en el que obtener el directorio de lang por defecto en la función startUpLang()
require_once (DOKU_INC . 'inc/pageutils.php');
require_once (DOKU_INC . 'inc/parserutils.php');

class WikiIocLangManager {
    private static $langLoaded = FALSE;
    private static $pluginLangLoaded = array();


    public static function getXhtml($key){
        return p_locale_xhtml($key);
    }

    public static function isTemplate($key){
        return page_exists($key);
    }

    public static function getXhtmlTemplate($key){
        $ret = "";
        $file = wikiFN($key);
        if(file_exists($file)){
            $ret = p_cached_output($file,'xhtml',$key);
        }
        return $ret;
    }

    public static function getRawTemplate($key){
        $ret = "";
        $file = wikiFN($key);
        if(file_exists($file)){
            $ret = rawWiki($key);
        }
        return $ret;
    }

    public static function isXhtmlKey($key){
        return file_exists(localeFN($key));
    }

    public static function isKey($key, $plugin=""){
        $value = self::getLang($key, $plugin);
        return $value!==$key;
    }

    public static function getLang($key, $plugin=""){
        global $lang;
        self::load($plugin);
        if (empty($plugin)){
            $value = $lang[$key];
        }else{
            $value = self::$pluginLangLoaded[$plugin][$key];
        }
        if (!isset($value)) {
            $value = $key;
        }

        return $value;
    }

    public static function load($plugin="") {
        if (!self::$langLoaded) {
            self::startUpLang();
        }
        if(!empty($plugin)){
            if(!isset(self::$pluginLangLoaded[$plugin])){
                self::startUpPluginLang($plugin);
            }
        }
    }

    private static function startUpLang() {
        global $lang;
        $idioma = WikiGlobalConfig::getConf("lang");
        $tplIncDir = WikiGlobalConfig::tplIncDir();
        //get needed language array
        include $tplIncDir."lang/en/lang.php";
        if ( !empty($idioma) && $idioma !== "en" && file_exists("$tplIncDir/lang/$idioma/lang.php") ) {
            include "$tplIncDir/lang/$idioma/lang.php";
        }
	//[JOSEP] TODO: Caldrà traslladar el LANG de wikiiocmodel a un altre lloc que escollirem en un altre moment
        include DOKU_PLUGIN."wikiiocmodel/lang/en/lang.php";;
        if ( !empty($idioma) && $idioma !== "en" && file_exists(DOKU_PLUGIN."wikiiocmodel/lang/$idioma/lang.php") ) {
            include DOKU_PLUGIN."wikiiocmodel/lang/$idioma/lang.php";
        }
        self::$langLoaded=true;
    }

    private static function startUpPluginLang($plugin) {
        $idioma = WikiGlobalConfig::getConf("lang");

        $lang = array();
        $path = DOKU_PLUGIN . $plugin."/lang/";
        // don't include once, in case several plugin components require the same language file
        @include($path."en/lang.php");
        if ($idioma != 'en')
            @include($path.$idioma."/lang.php");

        self::$pluginLangLoaded[$plugin] = $lang;
    }
}
