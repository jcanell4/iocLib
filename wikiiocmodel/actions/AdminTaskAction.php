<?php
/**
 * Description of AdminTaskAction
 * @author josep
 */
if (!defined('DOKU_INC')) die();
require_once DOKU_INC."inc/pluginutils.php";
require_once DOKU_INC."inc/actions.php";

class AdminTaskAction extends DokuAction{

    private $dataTmp;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_EXPORT_ADMIN;
    }

    protected function startProcess(){
        global $ACT, $ID, $_REQUEST;

        // Agafem l'index de la configuració
        if ( !isset( $this->params[AdminKeys::KEY_ID] ) ) {
            $this->params[AdminKeys::KEY_ID]  = WikiIocInfoManager::getInfo('start');
        }

        $ID = $this->params[AdminKeys::KEY_ID];
        $ACT = $this->params[AdminKeys::KEY_DO] = PageKeys::DW_ACT_EXPORT_ADMIN;

        if ( !$this->params[AdminKeys::KEY_TASK] ) {
            $this->params[AdminKeys::KEY_TASK]=  $this->params[AdminKeys::KEY_PAGE];
        }
        if ( !$_REQUEST[AdminKeys::KEY_PAGE] || $_REQUEST[AdminKeys::KEY_PAGE] != $this->params[AdminKeys::KEY_TASK] ) {
            $_REQUEST[AdminKeys::KEY_PAGE] = $this->params[AdminKeys::KEY_TASK];
        }
    }

    protected function runProcess(){
        global $ACT;

        $ACT = IocCommon::act_permcheck( $ACT );
        //handle admin tasks
        // retrieve admin plugin name from $_REQUEST['page']
        if ( ! empty( $_REQUEST['page'] ) ) {
            $pluginlist = plugin_list( 'admin' );
            if ( in_array( $_REQUEST['page'], $pluginlist ) ) {
                // attempt to load the plugin
                if ( $plugin =& plugin_load( 'admin', $_REQUEST['page'] ) !== NULL ) {
                    if ( $plugin->forAdminOnly() && !WikiIocInfoManager::getInfo('isadmin') ) {
                        // a manager tried to load a plugin that's for admins only
                        unset( $_REQUEST['page'] );
                        msg( 'For admins only', - 1 );
                    } else {
                        if ( is_callable( array($plugin, "preventRefresh") ) ) {
                            $allowedRefresh = $plugin->preventRefresh();
                        }
                        $plugin->handle();
                        $this->dataTmp["needRefresh"] = is_callable( array( $plugin, "isRefreshNeeded" ) );
                        if ( $this->dataTmp["needRefresh"] ) {
                            $this->dataTmp["needRefresh"] = $plugin->isRefreshNeeded();
                        }
                        $this->dataTmp["title"] = $plugin->getMenuText( WikiIocInfoManager::getInfo('lang') );
                        if ( isset($allowedRefresh) && is_callable( array($plugin, "setAllowedRefresh") ) ) {
                            $plugin->setAllowedRefresh( $allowedRefresh );
                        }
                    }
                }
            }
        }
        // check permissions again - the action may have changed
        $ACT = IocCommon::act_permcheck( $ACT );
    }

    /**
     * És un mètode per sobrescriure. Per defecte no fa res, però la
     * sobrescriptura permet generar la resposta a enviar al client. Aquest
     * mètode ha de retornar la resposa o bé emmagatzemar-la a l'atribut
     * DokuAction#response.
     */
    protected function responseProcess(){
        $response=array();

        $id = "admin_" . $this->params[AdminKeys::KEY_TASK];

        if ( ! $this->dataTmp["needRefresh"] ) {
            $pageToSend = $this->getAdminTaskHtml();
            $response   = $this->getCommonPage( $id, $this->params[AdminKeys::KEY_TASK], $pageToSend );
        }
        $response["needRefresh"] = $this->dataTmp["needRefresh"];

        // Informació a pantalla
        $info_time_visible = 5;
        switch ( $_REQUEST['page'] ) {
            case 'config':
                if ( ! $response['needRefresh'] ) {
                    if ( isset( $_REQUEST['do'] ) ) {
                        $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_loaded'), $id, $info_time_visible );
                    } else {
                        $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('button_clicked') . '"'.\WikiIocLangManager::getLang('button_desa').'"', $id, $info_time_visible );
                    }
                }
                break;
            case 'extension':
                if (empty($_REQUEST['fn'])) {
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_loaded'), $id, $info_time_visible);
                    break;
                }else {
                    $fn = $_REQUEST['fn'];
                    $button = is_array($fn) ? key($fn) : $fn;
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('button_clicked') . '"'.$button.'"', $id, $info_time_visible);
                }
                break;
            case 'plugin':
                switch ( key( $_REQUEST['fn'] ) ) {
                    case NULL:
                        // call from the admin tab
                        $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_loaded'), $id, $info_time_visible );
                        break;
                    default:
                        // call from the user plugin tab
                        $fn = $_REQUEST['fn'];
                        if ( is_array( $fn[ key( $fn ) ] ) ) {
                            $fn = $fn[ key( $fn ) ];
                        }
                        $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('button_clicked') . '"'. $fn[key($fn)].'"', $id, $info_time_visible );
                }
                break;
            case 'acl':
                switch ( $_REQUEST['cmd'] ) {
                    case NULL:
                        $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_loaded'), $id );
                        break;
                    case 'del':
                        $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_perm_delete'), $id, $info_time_visible );
                        break;
                    case 'save':
                    case 'update':
                        $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_perm_update'), $id, $info_time_visible );
                        break;
                    default:
                        $response['info'] = self::generateInfo("info", $_REQUEST['cmd'], $id );
                }
                break;
            case 'usermanager':
                $fn  = $_REQUEST['fn'];
                $key = ($fn) ? key($fn) : NULL;
                if ( ! isset( $key ) ) {
                    // call from the admin tab
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_loaded'), $id, $info_time_visible );
                } else {
                    // call from the user plugin tab
                    $cmd = (is_array($fn)) ? key($fn) : $fn;

                    switch ( $cmd ) {
                        case "add":
                        case "delete":
                        case "export" :
                        case "import" :
                        case "importfails":
                        case "modify":
                        case "start":
                        case "next":
                        case "last":
                        case "prev":
                            $param = $fn[ key( $fn ) ];
                            break;
                        case "edit"   :
                            $param = \WikiIocLangManager::getLang('button_edit_user');
                            break;
                        case "search" :
                            $param = \WikiIocLangManager::getLang('button_filter_user');
                            break;
                    }
                    $response['info']   = self::generateInfo( "info", \WikiIocLangManager::getLang('button_clicked') . '"' . $param . '"', $id, $info_time_visible );
                    $response['iframe'] = TRUE;
                }
                break;
            case "revert":
                if ( isset( $_REQUEST['revert'] ) ) {
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('button_clicked'). '"' . \WikiIocLangManager::getLang('button_revert') . '"', $id, $info_time_visible );
                } else if ( isset( $_REQUEST['filter'] ) ) {
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('button_clicked') . '"'. \WikiIocLangManager::getLang('button_cercar') . '"', $id, $info_time_visible );
                } else {
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_loaded'), $id, $info_time_visible );
                }
                break;
            case "latex":
                if ( isset( $_REQUEST['latexpurge'] ) ) {
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('button_clicked') . '"' . $_REQUEST['latexpurge'] . '"', $id, $info_time_visible );
                } else if ( isset( $_REQUEST['dotest'] ) ) {
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('button_clicked') . '"' . $_REQUEST['dotest'] . '"', $id, $info_time_visible );
                } else {
                    $response['info'] = self::generateInfo("info", \WikiIocLangManager::getLang('admin_task_loaded'), $id, $info_time_visible );
                }
                break;
            case "smtp":
                break;
            default:
                $response['info'] = self::generateInfo("info", "Emplenar a DokumodelAdapter->getAdminTask:" . $_REQUEST['page'], $id );
                break;
        }

        return $response;
    }

    private function getAdminTaskHtml() {
        global $ACT;
        ob_start();
        trigger_event( 'TPL_ACT_RENDER', $ACT, "tpl_admin" );
        $html_output = ob_get_clean();
        ob_start();
        trigger_event('TPL_CONTENT_DISPLAY', $html_output, 'ptln');
        $html_output = ob_get_clean();

        return $html_output;
    }
}

