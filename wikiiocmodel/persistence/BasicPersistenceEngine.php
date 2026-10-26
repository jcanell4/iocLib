<?php
/**
 * Description of BasicPersistenceEngine
 *
 * @author josep
 */
if (! defined('DOKU_INC')) die();
if (!defined('DOKU_LIB_IOC')) define('DOKU_LIB_IOC', DOKU_INC.'lib/lib_ioc/');
if (!defined('DOKU_PERSISTENCE')) define('DOKU_PERSISTENCE', DOKU_LIB_IOC.'wikiiocmodel/persistence/');

class BasicPersistenceEngine {

    public function createPageDataQuery(){
        require_once(DOKU_PERSISTENCE . 'PageDataQuery.php');
        return new PageDataQuery();
    }

    public function createMediaDataQuery(){
        require_once(DOKU_PERSISTENCE . 'MediaDataQuery.php');
        return new MediaDataQuery();
    }

    public function createMediaMetaDataQuery(){
        require_once(DOKU_PERSISTENCE . 'MediaMetaDataQuery.php');
        return new MediaMetaDataQuery();
    }

    public function createMetaDataQuery(){
        require_once(DOKU_PERSISTENCE . 'MetaDataQuery.php');
        return new MetaDataQuery();
    }

    public function createDraftDataQuery(){
        require_once(DOKU_PERSISTENCE . 'DraftDataQuery.php');
        return new DraftDataQuery();
    }

    public function createNotifyDataQuery(){
        require_once(DOKU_PERSISTENCE . 'NotifyDataQuery.php');
        return new NotifyDataQuery();
    }

    public function getNotifyDataQueryClass(){
        require_once(DOKU_PERSISTENCE . 'NotifyDataQuery.php');
        return NotifyDataQuery::class;
    }

    public function createLockDataQuery(){
        require_once(DOKU_PERSISTENCE . 'LockDataQuery.php');
        return new LockDataQuery();
    }

    public function createProjectMetaDataQuery($projectId=FALSE, $projectSubset=FALSE, $projectType=FALSE, $revision=FALSE){
        require_once(DOKU_PERSISTENCE . 'ProjectMetaDataQuery.php');
        return new ProjectMetaDataQuery($projectId, $projectSubset, $projectType, $revision);
    }
}
