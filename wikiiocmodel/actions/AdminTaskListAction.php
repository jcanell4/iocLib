<?php
/**
 * Description of AdminTaskListAction
 * @author josep
 */
if (!defined('DOKU_INC')) die();
require_once DOKU_INC . "inc/pluginutils.php";
require_once DOKU_INC . "inc/actions.php";

class AdminTaskListAction extends AdminTaskAction {

    private $pageToSend;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
    }

    protected function runProcess() {
        global $ACT;
        if(WikiIocInfoManager::getInfo("ismanager")){
            WikiIocInfoManager::setInfo("perm", 1);
        }
        $ACT = IocCommon::act_permcheck($ACT);
        $this->pageToSend = $this->getAdminTaskListHtml();
    }

    protected function responseProcess(){
        $ret = $this->getCommonPage($id, WikiIocLangManager::getLang('btn_admin'), $this->pageToSend);
        return $ret;
    }

    private function getAdminTaskListHtml() {
            global $conf, $ACT;

            ob_start();
            trigger_event( 'TPL_ACT_RENDER', $ACT );

            // build menu of admin functions from the plugins that handle them
            $pluginlist = plugin_list( 'admin' );
            $menu       = array();
            foreach ( $pluginlist as $p ) {
                    if ( $obj =& plugin_load( 'admin', $p ) === NULL ) {
                            continue;
                    }

                    // check permissions
                    if ( $obj->forAdminOnly() && !WikiIocInfoManager::getInfo('isadmin')) {
                            continue;
                    }

                    $menu[ $p ] = array(
                            'plugin' => $p,
                            'prompt' => $obj->getMenuText( $conf['lang'] ),
                            'sort'   => $obj->getMenuSort()
                    );
            }

            // Admin Tasks
            if ( count( $menu ) ) {
                    usort( $menu, 'p_sort_modes' );
                    // output the menu
                    ptln( '<div class="clearer"></div>' );
                    print p_locale_xhtml( 'adminplugins' );
                    ptln( '<ul>' );
                    foreach ( $menu as $item ) {
                            if ( ! $item['prompt'] ) {
                                    continue;
                            }
                            ptln( '  <li><div class="li"><a href="' . DOKU_BASE . DOKU_SCRIPT . '?'
                                  . 'do=admin&amp;page=' . $item['plugin'] . '">' . $item['prompt']
                                  . '</a></div></li>' );
                    }
                    ptln( '</ul>' );
            }

            $html_output = ob_get_clean();
            ob_start();
            trigger_event('TPL_CONTENT_DISPLAY', $html_output, 'ptln');
            $html_output = ob_get_clean();


            return $html_output;
    }
}
