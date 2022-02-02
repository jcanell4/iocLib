<?php
/**
 * class AdminModel
 * @author Rafael
 */
if (!defined('DOKU_INC')) die();

class AdminModel extends AbstractWikiModel {

    //JOSEP: No tinc clara la seva utilitat!. Hauríem de definir bé la funció que es demana a AdminModel
    // i jo crec que hauria de resoldre les utilitats demanades a través que qualsevol AdminAction o UtilAction o ...Action
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
     * @param array $projectsType tipus de projecte
     * @param array $callback funció de filtre per a la selecció de projectes: ['function'=>, 'params'=>]
     * @return array
     */
    public function selectProjectsByField($projectsType, $callback) {
        return $this->getProjectMetaDataQuery()->selectProjectsByField($callback, $projectsType);
    }

    /** Obtiene la lista de proyectos del tipo indicado
     * @param array $projectsType tipus de projecte
     * @return array
     */
    public function selectProjectsByType($projectsType) {
        return $this->getProjectMetaDataQuery()->selectProjectsByType($projectsType);
    }

    //Obtiene un array [key, value] con los datos del proyecto solicitado
    public function getDataProject($id=FALSE, $projectType=FALSE, $metaDataSubSet=FALSE) {
        $values = $this->getProjectMetaDataQuery()->getDataProject($id, $projectType, $metaDataSubSet);
        return $values;
    }

    public function getData() {
        throw new UnavailableMethodExecutionException();
    }

    public function setData($toSet) {
        throw new UnavailableMethodExecutionException();
    }

}
