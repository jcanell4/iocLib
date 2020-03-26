<?php
/**
 * Description of WikiPageSystemManager
 * @author Josep Cañellas
 */
if (!defined("DOKU_INC")) die();
require_once DOKU_INC . 'inc/pageutils.php';


class WikiPageSystemManager {
    public static $DEFAULT_FORMAT = 0;
    public static $SHORT_FORMAT = 1;

    public static function getRealDirFromPages($ns, $clean=true){
        return self::getRealDir($ns, "datadir", $clean);
    }

    public static function getRealDirFromMeta($ns, $clean=true){
        return self::getRealDir($ns, "metadir", $clean);
    }

    public static function getRealDirFromMediaMeta($ns, $clean=true){
        return self::getRealDir($ns, "mediametadir", $clean);
    }

    public static function getRealDirFromMedia($ns, $clean=true){
        return self::getRealDir($ns, "mediadir", $clean);
    }

    public static function getRealDirFromMediaOld($ns, $clean=true){
        return self::getRealDir($ns, "mediaolddir", $clean);
    }

    public static function getRealDir($ns, $repositoryType, $clean=true){
        $id = $ns;

        if ($clean) $id = cleanID($id);
        $id = str_replace(':','/',$id);
        $dir = WikiGlobalConfig::getConf("datadir").'/'.$id.'/';
        return $dir;
    }

    public static function cleanPageID( $raw_id) {
        return cleanID($raw_id);
    }

    public static function getContainerIdFromPageId($id) {
            return str_replace( ':', '_', $id );
    }

    /**
     * Extreu la data a partir del nombre de revisió
     *
     * @param int $revision - nombre de la revisió
     * @param int $mode     - format de la data
     *
     * @return string - Data formatada
     *
     */
    public static function extractDateFromRevision( $revision, $mode = NULL ) {
        if(!$revision){
            return NULL;
        }

        if(!$mode){
            $mode = self::$DEFAULT_FORMAT;
        }

        switch ( $mode ) {

                case self::$SHORT_FORMAT:
                        $format = "d-m-Y";
                        break;

                case self::$DEFAULT_FORMAT:

                default:
                        $format = "d-m-Y H:i:s";

        }

        return date( $format, $revision );
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
    public static function getRecentList($first=0, $show_changes='both', $id =''){
        /* we need to get one additionally log entry to be able to
         * decide if this is the last page or is there another one.
         * This is the cheapest solution to get this information.
         */
        $ret = array();
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

        $ret['header'] =  WikiIocLangManager::getXhtml('recent');

        if (getNS($id) != '')
            $ret['header'] .= '<div class="level1"><p>' . sprintf(WikiIocLangManager::getLang('recent_global'), getNS($id), wl('', 'do=recent')) . '</p></div>';

        $form = new Doku_Form(array('id' => 'dw__recent', 'method' => 'GET', 'class' => 'changes'));
        $form->addHidden('sectok', null);
        $form->addHidden('do', 'recent');
        $form->addHidden('id', $id);

        if (WikiGlobalConfig::getConf('mediarevisions')) {
            $form->addElement('<div class="changeType">');
            $form->addElement(form_makeListboxField(
                        'show_changes',
                        array(
                            'pages'      => WikiIocLangManager::getLang('pages_changes'),
                            'mediafiles' => WikiIocLangManager::getLang('media_changes'),
                            'both'       => WikiIocLangManager::getLang('both_changes')),
                        $show_changes,
                        WikiIocLangManager::getLang('changes_type'),
                        '','',
                        array('class'=>'quickselect')));

            $form->addElement(form_makeButton('submit', 'recent', WikiIocLangManager::getLang('btn_apply'))); //CANVIAR PER CRIDA AJAX
            $form->addElement('</div>');
        }

        $form->addElement(form_makeOpenTag('ul', array("id" => "recents_list_area")));


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

            if ($recent['media']) {
                $diff = (count(getRevisions($recent['id'], 0, 1, 8192, true)) && @file_exists(mediaFN($recent['id'])));
                if ($diff) {
                    $href = media_managerURL(array('tab_details' => 'history',
                        'mediado' => 'diff', 'image' => $recent['id'], 'ns' => getNS($recent['id'])), '&');
                }
            } else {
                $href = wl($recent['id'],"do=diff", false, '&');
            }

            if ($recent['media'] && !$diff) {
                $form->addElement('<img src="'.DOKU_BASE.'lib/images/blank.gif" width="15" height="11" alt="" />');
            } else {
                $form->addElement(form_makeOpenTag('a', array('class' => 'diff_link', 'href' => $href)));
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
                $href = media_managerURL(array('tab_details' => 'history',
                    'image' => $recent['id'], 'ns' => getNS($recent['id'])), '&');
            } else {
                $href = wl($recent['id'],"do=revisions",false,'&');
            }
            $form->addElement(form_makeOpenTag('a', array('class' => 'revisions_link', 'href' => $href)));
            $form->addElement(form_makeTag('img', array(
                            'src'   => DOKU_BASE.'lib/images/history.png',
                            'width' => 12,
                            'height'=> 14,
                            'title' => WikiIocLangManager::getLang('btn_revs'),
                            'alt'   => WikiIocLangManager::getLang('btn_revs')
                            )));
            $form->addElement(form_makeCloseTag('a'));

            if ($recent['media']) {
                $href = media_managerURL(array('tab_details' => 'view', 'image' => $recent['id'], 'ns' => getNS($recent['id'])), '&');
                $class = (file_exists(mediaFN($recent['id']))) ? 'wikilink1' : $class = 'wikilink2';
                $form->addElement(form_makeOpenTag('a', array('class' => $class, 'href' => $href)));
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

        $form->addElement(form_makeOpenTag('div', array('class' => 'pagenav')));
        $last = $first + WikiGlobalConfig::getConf('recent');
        if ($first > 0) {
            $first -= WikiGlobalConfig::getConf('recent');
            if ($first < 0) $first = 0;
            $form->addElement(form_makeOpenTag('div', array('class' => 'pagenav-prev')));
            $form->addElement(form_makeTag('input', array(
                        'type'  => 'submit',
                        'name'  => 'first['.$first.']',
                        'value' => WikiIocLangManager::getLang('btn_newer'),
                        'accesskey' => 'n',
                        'title' => WikiIocLangManager::getLang('btn_newer').' [N]',
                        'class' => 'button show'
                        )));
            $form->addElement(form_makeCloseTag('div'));
        }
        if ($hasNext) {
            $form->addElement(form_makeOpenTag('div', array('class' => 'pagenav-next')));
            $form->addElement(form_makeTag('input', array(
                            'type'  => 'submit',
                            'name'  => 'first['.$last.']',
                            'value' => WikiIocLangManager::getLang('btn_older'),
                            'accesskey' => 'p',
                            'title' => WikiIocLangManager::getLang('btn_older').' [P]',
                            'class' => 'button show'
                            )));
            $form->addElement(form_makeCloseTag('div'));
        }
        $form->addElement(form_makeCloseTag('div'));
        html_form('recent', $form);
    }

}
