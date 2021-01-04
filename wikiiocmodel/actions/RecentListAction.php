<?php
/**
 * Description of RecentListAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();
require_once DOKU_INC . "inc/changelog.php";
require_once DOKU_INC . "inc/html.php";

class RecentListAction extends DokuAction{
    private $content;

    public function init($modelManager=NULL) {
        parent::init($modelManager);
        $this->defaultDo = PageKeys::DW_ACT_RECENT;
    }

    protected function responseProcess() {
        $this->response =[
            'id' => "recent_list",
            'title' => WikiIocLangManager::getLang("recent_list"),
            "content" => $this->content,
            'type' => "html"
        ];
        return $this->response;
    }

    protected function runProcess() {
        $this->content = $this->getRecentList(
                    $this->params[PageKeys::FIRST_KEY],
                    $this->params[PageKeys::SHOW_CHANGES_KEY],
                    $this->params[PageKeys::KEY_ID]
                );
    }

    protected function startProcess() {
        global $ACT;
        $ACT = $this->params[PageKeys::KEY_DO] = PageKeys::DW_ACT_RECENT;
    }

    /**
     * get recent changes
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Matthias Grimm <matthiasgrimm@users.sourceforge.net>
     * @author Ben Coburn <btcoburn@silicodon.net>
     * @author Kate Arzamastseva <pshns@ukr.net>
     * @author Josep Cañellas <jcanell4@ioc.cat>
     */
    private function getRecentList($first=0, $show_changes='both', $id=''){
        $ret = array();

        /* we need to get one additionally log entry to be able to
         * decide if this is the last page or is there another one.
         * This is the cheapest solution to get this information.
         */
        $ret['formId'] = $formId = 'dw__recent';
        $flags = 0;
        if ($show_changes == 'mediafiles' && WikiGlobalConfig::getConf('mediarevisions')) {
            $flags = RECENTS_MEDIA_CHANGES;
        } elseif ($show_changes == 'pages') {
            $flags = 0;
        } elseif (WikiGlobalConfig::getConf('mediarevisions')) {
            $show_changes = 'both';
            $flags = RECENTS_MEDIA_PAGES_MIXED;
        }

        $recents = getRecents($first,WikiGlobalConfig::getConf('recent') + 1,getNS($id),$flags);
        if(count($recents) == 0 && $first != 0){
            $first=0;
            $recents = getRecents($first,  WikiGlobalConfig::getConf('recent') + 1,getNS($id),$flags);
        }
        $hasNext = false;
        if (count($recents)>WikiGlobalConfig::getConf('recent')) {
            $hasNext = true;
            array_pop($recents); // remove extra log entry
        }

        $ret['list'] = WikiIocLangManager::getXhtml('recent');

        if (getNS($id) != '')
            $ret['list'] .= '<div class="level1"><p>' . sprintf(WikiIocLangManager::getLang('recent_global'), getNS($id), wl('', 'do=recent')) . '</p></div>';

        $form = new Doku_Form(array('id' => $formId, 'name'=>$formId,  'method' => 'GET', 'class' => 'changes'));
        $form->addHidden('sectok', null);
//        $form->addHidden('do', 'recent');
        $form->addHidden('id', $id);

        if (WikiGlobalConfig::getConf('mediarevisions')) {
            $ret['form_controls'] = '<div class="changeType">';
            $ret['form_controls'] .= '<fieldset>';
            $ret['form_controls'] .= '<legend>'.WikiIocLangManager::getLang('changes_type_filter').'</legend>';

            $ret['form_controls'] .= form_listboxfield(form_makeListboxField(
                        'show_changes',
                        array(
                            'pages'      => WikiIocLangManager::getLang('pages_changes'),
                            'mediafiles' => WikiIocLangManager::getLang('media_changes'),
                            'both'       => WikiIocLangManager::getLang('both_changes')),
                        $show_changes,
                        '', //WikiIocLangManager::getLang('changes_type'),
                        '','',
                        array('form' => $formId, 'class'=>'quickselect')));

            $ret['form_controls'] .= form_button(form_makeButton('submit', 'recent', WikiIocLangManager::getLang('btn_apply'), array('form' => $formId)));
            $ret['form_controls'] .= '</fieldset>';
            $ret['form_controls'] .= '</div>';
        }

        $form->addElement(form_makeOpenTag('ul'));

        foreach($recents as $recent){
            $date = dformat($recent['date']);
            if ($recent['type']===DOKU_CHANGE_TYPE_MINOR_EDIT)
                $form->addElement(form_makeOpenTag('li', array('class' => 'minor')));
            else
                $form->addElement(form_makeOpenTag('li'));

            $form->addElement(form_makeOpenTag('div', array('class' => 'li')));

            if ($recent['media']) {
                $form->addElement(media_printicon($recent['id']));
            } else {
                $icon = DOKU_BASE.'lib/images/fileicons/file.png';
                $form->addElement('<img src="'.$icon.'" alt="'.$recent['id'].'" class="icon" />');
            }

            $form->addElement(form_makeOpenTag('span', array('class' => 'date')));
            $form->addElement($date);
            $form->addElement(form_makeCloseTag('span'));

            $diff = false;
            $href = '';
            $dataCall = '';

            if ($recent['media']) {
                $medialog = new MediaChangeLog($recent['id']);
                $diff = (count($medialog->getRevisions(0, 1)));
                if ($diff) {
                    $dataCall = 'mediadetails';
                    $href = media_managerURL(array('tab_details' => 'history',
                        'mediado' => 'diff', 'image' => $recent['id'], 'ns' => getNS($recent['id'])), '&');
                }
            } else {
                $dataCall = 'diff';
                $href = wl($recent['id'],"do=diff", false, '&');
            }

            if ($recent['media'] && !$diff) {
                $form->addElement('<img src="'.DOKU_BASE.'lib/images/blank.gif" width="15" height="11" alt="" />');
            } else {
                $form->addElement(form_makeOpenTag('a', array('data-call' => $dataCall, 'class' => 'diff_link', 'href' => $href)));
                $form->addElement(form_makeTag('img', array(
                                'src'   => DOKU_BASE.'lib/images/diff.png',
                                'width' => 15,
                                'height'=> 11,
                                'title' => WikiIocLangManager::getLang('diff'),
                                'alt'   => WikiIocLangManager::getLang('diff')
                                )));
                $form->addElement(form_makeCloseTag('a'));
            }

            if ($recent['media']) {
                $href = media_managerURL(array('tab_details' => 'view', 'image' => $recent['id'], 'ns' => getNS($recent['id'])), '&');
                $class = (file_exists(mediaFN($recent['id']))) ? 'wikilink1' : $class = 'wikilink2';
                $form->addElement(form_makeOpenTag('a', array('data-call'=>'mediadetails', 'class' => $class, 'href' => $href)));
                $form->addElement($recent['id']);
                $form->addElement(form_makeCloseTag('a'));
            } else {
                $form->addElement(html_wikilink(':'.$recent['id'],useHeading('navigation')?null:$recent['id']));
            }
            $form->addElement(form_makeOpenTag('span', array('class' => 'sum')));
            $form->addElement(' – '.htmlspecialchars($recent['sum']));
            $form->addElement(form_makeCloseTag('span'));

            $form->addElement(form_makeOpenTag('span', array('class' => 'user')));
            if($recent['user']){
                $form->addElement('<bdi>'.editorinfo($recent['user']).'</bdi>');
                if(auth_ismanager()){
                    $form->addElement(' <bdo dir="ltr">('.$recent['ip'].')</bdo>');
                }
            }else{
                $form->addElement('<bdo dir="ltr">'.$recent['ip'].'</bdo>');
            }
            $form->addElement(form_makeCloseTag('span'));

            $form->addElement(form_makeCloseTag('div'));
            $form->addElement(form_makeCloseTag('li'));
        }
        $form->addElement(form_makeCloseTag('ul'));

        $ret['form_controls'] .= form_opentag(form_makeOpenTag('div', array('class' => 'pagenav')));
        if($first >0 || $hasNext){
            $ret['form_controls'] .= '<fieldset>';
            $ret['form_controls'] .= '<legend>'.WikiIocLangManager::getLang('changes_navigation').'</legend>';
        }

        $last = $first + WikiGlobalConfig::getConf('recent');
        if ($first > 0) {
            $first -= WikiGlobalConfig::getConf('recent');
            if ($first < 0) $first = 0;
            $ret['form_controls'] .= form_opentag(form_makeOpenTag('span', array('class' => 'pagenav-prev')));
            $ret['form_controls'] .= form_tag(form_makeTag('input', array(
                        'form' => $formId,
                        'type'  => 'submit',
                        'name'  => 'first['.$first.']',
                        'value' => WikiIocLangManager::getLang('btn_newer'),
                        'accesskey' => 'n',
                        'title' => WikiIocLangManager::getLang('btn_newer').' [N]',
                        'class' => 'button show'
                        )));
            $ret['form_controls'] .= form_closetag(form_makeCloseTag('span'));
        }
        if ($hasNext) {
            $ret['form_controls'] .= form_opentag(form_makeOpenTag('span', array('class' => 'pagenav-next')));
            $ret['form_controls'] .= form_tag(form_makeTag('input', array(
                            'form' => $formId,
                            'type'  => 'submit',
                            'name'  => 'first['.$last.']',
                            'value' => WikiIocLangManager::getLang('btn_older'),
                            'accesskey' => 'p',
                            'title' => WikiIocLangManager::getLang('btn_older').' [P]',
                            'class' => 'button show'
                            )));
            $ret['form_controls'] .= form_closetag(form_makeCloseTag('span'));
        }

        if($first >0 || $hasNext){
            $ret['form_controls'] .= '</fieldset>';
            $ret['form_controls'] .= form_closetag(form_makeCloseTag('div'));
        }

        $form->addElement(form_makeCloseTag('div'));
        $ret['list'] .= $form->getForm();
        return $ret;
    }

}
