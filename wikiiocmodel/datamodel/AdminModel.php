<?php
/**
 * AdminModel
 * @author Rafael
 */
if (!defined('DOKU_INC')) die();

class AdminModel extends AbstractWikiModel {

    protected $id;
    protected $projectMetaDataQuery;

    public function __construct($persistenceEngine) {
        parent::__construct($persistenceEngine);
        $this->projectMetaDataQuery = $persistenceEngine->createProjectMetaDataQuery();
    }

    public function init($id) {
        $this->id = $id;
    }

    public function getId(){
        return $this->id;
    }

    public function getProjectMetaDataQuery() {
        return $this->projectMetaDataQuery;
    }

    public function getListProjectTypes($all=FALSE) {
        return $this->getProjectMetaDataQuery()->getListProjectTypes($all);
    }

    public function getData() {}
    public function setData($toSet) {}

}
