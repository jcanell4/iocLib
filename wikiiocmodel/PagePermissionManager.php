<?php
/**
 * PagePermissionManager: Gestión de permisos, de los usuarios sobre las páginas, en el fichero acl.auth.php
 * @author Rafael Claver
 */
if (!defined('DOKU_INC') ) die();

class PagePermissionManager{

    /**
     * Obtiene la lista de usuarios, grupos o todos, que tienen, como mínimo, permiso $permis sobre la página $id
     * @param $type: 'users', 'groups', 'all'
     */
    public static function getListUsersPagePermission($id, $permis=AUTH_CREATE, $type='users') {
        global $auth;
        $acl_class = new admin_plugin_acl();
        $acl_class->handle();
        $acl = self::get_acl();


        $ret = array();
        $superusers = WikiGlobalConfig::getConf("superuser").','.WikiGlobalConfig::getConf("manager");
        $ret = array_map("trim", explode(",", $superusers));

        foreach ($ret as $key => $value){
            if($value[0]==="@"){
                unset($ret[$key]);
            }
        }

        $arrayAdminsFromDb = $auth->retrieveUsers(0, 100, ["grps" => array('admin', 'manager')]);

        foreach ($arrayAdminsFromDb as $key => $info){
            $ret[] = $key;
        }

        $camins = explode(":", $id);
        for ($c = count($camins)-1; $c >= 0; $c--) {
            $camí = implode(":", $camins);
            if ($acl[$camí]) {
                foreach ($acl[$camí] as $k => $v) {
                    switch ($type) {
                        case 'users': $inc = (substr($k,0,1)!=="@"); break;
                        case 'groups': $inc = (substr($k,0,1)==="@"); break;
                        default: $inc = true; break;
                    }
                    if ($inc && $v >= $permis) $ret[] = $k;
                }
            }
            if ($c > 0) {
                if ($camins[$c] === "*") {
                    array_pop($camins);     //elimina último elemento del array
                    $camins[$c-1] = "*";    //sube un nivel de directorio
                }else {
                    $camins[$c++] = "*";    //sube un nivel de directorio
                }
            }
        }
        $ret = array_unique($ret);
        asort($ret);
        return array_values($ret);
    }

    /**
     * Obtiene un array de usuarios cuyo username o nombre contiene el texto $filter
     * @param $cadena es la parte del nombre que se busca
     * @return array[username=>[array['username'=>username, 'firstname'=>firstname, 'lastname'=>lastname]]
     */
    public static function getUserList($filter, $start=0, $pagesize=100) {
        global $auth;
        $au = & $auth;
        $user_list = $au->retrieveUsers($start, $pagesize+1, array('username_name'=>$filter));
        foreach ($user_list as $k => $v) {
            $ul[] = array('username' => $k, 'name' => $v['name']);
        }
        if (count($user_list) > $pagesize) {
            $ret['info'] = ['hasmore' => TRUE];
            array_pop($ul);
        }
        $ret['values'] = $ul;
        return $ret;
    }

    /**
     * @return int : valor del permiso actual del usuario $user sobre la página $page
     */
    public static function getPermissionPageForUser( $page, $user=NULL ) {
        global $auth;
        if (!$user) $user = $_SERVER['REMOTE_USER'];
	$info = $auth->getUserData($user);
        $permis = auth_aclcheck($page, $user, $info['grps']);
	return $permis;
    }

    /**
     * @param bool $force : true indica que s'ha d'establir estrictament el permís
     * @return int : valor del permiso actualmente establecido
     */
    public static function setPermissionPageForUser( $page, $user, $permis, $force=FALSE ) {
        global $auth;
	$acl_class = new admin_plugin_acl();
	$acl_class->handle();
	$info = $auth->getUserData($user);
        $permis_actual = auth_aclcheck($page, $user, $info['grps']);

        if ($force || $permis > $permis_actual) {
            $ret = $acl_class->_acl_add($page, $user, $permis);
            if ($ret) {
		if (strpos($page, '*') === FALSE) {
                    if ($permis > AUTH_EDIT) {
                        $permis_actual = AUTH_EDIT;
                    }
		} else {
                    $permis_actual = $permis;
		}
            }
	}
	return $permis_actual;
    }

    public static function deletePermissionPageForUser( $page, $user ) {
	if ( $page && $user ) {
            $acl_class = new admin_plugin_acl();
            $ret = $acl_class->_acl_del( $page, $user );
	}
	return $ret;
    }

    public static function updateMyOwnPagePermission($permission) {

        if ($permission->getIsMyOwnNs()) {  //si es tracta de la pròpia pàgina de l'usuari ...
            $user = WikiIocInfoManager::getInfo('client');
            $page = $permission->getIdPage();
            $ns = substr($page, 0, strrpos($page, ":")) . ':*';
            $ret = self::updatePagePermission($ns, $user, AUTH_DELETE);
            if ($ret) WikiIocInfoManager::setInfo('perm', $ret);
        }
        return $ret;
    }

    public static function updatePagePermission($page, $user, $permission, $force=TRUE) {

        $level = self::levelPageUser($page, $user);
        $exist = ($level) ? TRUE : FALSE;

        if ($exist && $level < $permission) {
            //No consta el permís suficient de la pàgina per a aquest usuari
            $ret = self::deletePermissionPageForUser($page, $user);
        }
        if (!$exist || $ret) {
            //No existeix el registre, o bé, no consta el permís suficient de la pàgina per a aquest usuari
            $ret = self::setPermissionPageForUser($page, $user, $permission, $force);
        }
        return ($ret) ? $ret : TRUE;
    }

    /**
     * Mira si en el fichero acl.auth.php existe un permiso sobre la página para el usuario
     * @return boolean : retorna true si ya existe una entrada para $page $user
     */
    public static function existPageUser($page, $user) {
        $ret = self::levelPageUser($page, $user);
        return ($ret) ? TRUE : FALSE;
    }

    /**
     * Obtiene del fichero acl.auth.php el nivel de permiso sobre la página para el usuario
     * @param string $page : wikiRuta de la página
     * @param string $user : nombre del usuario
     * @return int : retorna el nivel de permiso correspondiente a la entrada $page $user
     */
    public static function levelPageUser($page, $user) {
        $acl_class = new admin_plugin_acl();
        $acl_class->handle();
        return $acl_class->acl[$page][$user];
    }

    /**
     * Get current ACL settings as multidim array
     * @author Andreas Gohr <andi@splitbrain.org>
     * @culpable Rafael Claver
     */
    private static function get_acl(){
        global $AUTH_ACL;
        $acl_config = array();

        foreach($AUTH_ACL as $line){
            $line = trim(preg_replace('/#.*$/','',$line));  //ignore comments
            if ($line) {
                $acl = preg_split('/[ \t]+/',$line);        //0 is pagename, 1 is user, 2 is acl
                $acl[1] = rawurldecode($acl[1]);
                $acl_config[$acl[0]][$acl[1]] = $acl[2];
            }
        }
        ksort($acl_config);
        return $acl_config;
    }
}
