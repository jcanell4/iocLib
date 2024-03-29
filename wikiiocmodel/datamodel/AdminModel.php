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

    /** Obtiene la lista de proyectos de los tipos indicados filtrados por la función callback
     * @param array $projectsType tipus de projectes
     * @param array $callback funció de filtre per a la selecció de projectes: ['function'=>, 'params'=>]
     * @return array
     */
    public function selectProjectsByField($projectsType, $callback) {
        return $this->getProjectMetaDataQuery()->selectProjectsByField($callback, $projectsType);
    }

    /** Obtiene la lista de proyectos de los tipos indicados
     * @param array $projectsType tipus de projectes
     * @param array $branques : llista de branques de l'arbre de directoris on cal fer la cerca
     * @return array : llista dels projectes ['ns', 'projectType']
     */
    public function selectProjectsByType($projectsType, $branques="root") {
        return $this->getProjectMetaDataQuery()->selectProjectsByType($projectsType, $branques);
    }

    //Obtiene un array [key, value] con los datos del proyecto solicitado
    public function getDataProject($id=FALSE, $projectType=FALSE, $metaDataSubSet=FALSE) {
        return $this->getProjectMetaDataQuery()->getDataProject($id, $projectType, $metaDataSubSet);
    }

    // Obtiene un array Con todos los datos del proyecto (.mdpr en mdprojects/)
    public function getAllDataProject($id=FALSE, $projectType=FALSE) {
        return $this->getProjectMetaDataQuery()->getAllDataProject($id, $projectType);
    }

    // Averigua si el proyecto $id es de tipo workflow
    public function isProjectTypeWorkflow($projectType=NULL) {
        return $this->getProjectMetaDataQuery()->isProjectTypeWorkflow($projectType);
    }

    /**
     * Obtiene la lista usuarios del proyecto que tienen los roles indicados
     * @param array $rols : lista de roles a buscar
     * @return array Lista de usuarios del proyecto correspondientes a los roles indicados
     */
    public function getUserRol($rols, $id=NULL, $projectType=NULL) {
        $id = empty($id) ? $this->getId(): $id;
        $projectType = empty($projectType) ? $this->getProjectType($id): $projectType;
        $data = $this->projectMetaDataQuery->getDataProject($id, $projectType, NULL);
        foreach ($rols as $rol) {
            $users[] = $data[$rol];
        }
        return array_unique($users);
    }

    public function getProjectType($id) {
        return $this->getProjectMetaDataQuery()->getProjectType($id);
    }

    public function getData() {
        throw new UnavailableMethodExecutionException();
    }

    public function setData($toSet) {
        throw new UnavailableMethodExecutionException();
    }

}
