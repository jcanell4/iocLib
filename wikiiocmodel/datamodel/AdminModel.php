<?php
/**
 * class AdminModel
 * @author Rafael
 */
if (!defined('DOKU_INC')) die();

class AdminModel extends AbstractWikiModel {

    protected $id;
    protected $pageDataQuery;
    protected $projectMetaDataQuery;

    public function __construct($persistenceEngine) {
        parent::__construct($persistenceEngine);
        $this->pageDataQuery = $persistenceEngine->createPageDataQuery();
        $this->projectMetaDataQuery = $persistenceEngine->createProjectMetaDataQuery();
    }

    public function init($id) {
        $this->id = $id;
    }

    public function getId(){
        return $this->id;
    }

    public function getPageDataQuery() {
        return $this->pageDataQuery;
    }

    public function getProjectMetaDataQuery() {
        return $this->projectMetaDataQuery;
    }

    public function getListProjectTypes($all=FALSE) {
        return $this->getProjectMetaDataQuery()->getListProjectTypes($all);
    }

    /** Obtiene la lista de proyectos del tipo indicado filtrados por la función callback
     * @param string $projectType tipus de projecte
     * @param array $callback funció de filtre per a la selecció de projectes: ['function'=>, 'params'=>]
     * @return array
     */
    public function getProjects($projectType, $callback) {
        return $this->projectMetaDataQuery->selectProjectsByField($callback, [$projectType]);
    }

    //Obtiene un array [key, value] con los datos del proyecto solicitado
    public function getDataProject($id=FALSE, $projectType=FALSE, $metaDataSubSet=FALSE) {
        $values = $this->projectMetaDataQuery->getDataProject($id, $projectType, $metaDataSubSet);
        return $values;
    }

    public function getData() {}
    public function setData($toSet) {}

}
