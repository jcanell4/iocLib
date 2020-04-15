<?php
/**
 * ProjectFactoryAuthorization: Definició de les classes (fitxers de classe) d'autoritzacions de comandes
*         Associa un nom de classe a un fitxer d'autorització
*         Serveix pels noms de classe que no tenen un fitxer d'autorització
*         amb el seu nom (el nom de la comanda s'estableix amb el mètode setAuthorizationCfg() )
 * @author Rafael Claver
 */
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . "lib/plugins/");
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_PLUGIN . "wikiiocmodel/");

class ProjectFactoryAuthorization extends AbstractFactoryAuthorization {

    const DEFAULT_AUTH = WIKI_IOC_MODEL . "projects/defaultProject/authorization/";

    public function __construct($projectAuthPath = NULL) {
        parent::__construct( ($projectAuthPath) ? $projectAuthPath : self::DEFAULT_AUTH );
    }

    /* Noms de commanda que ja ténen un fitxer d'autorització amb el seu nom
     * 	'edit'        => 'edit' -> EditAuthorization.php
     *
     * Noms de comanda modificats amb el mètode setAuthorizationCfg()
     * 	'saveProject' => 'editProject'
     */
    public function setAuthorizationCfg() {
        $aCfg = ['_default'                     => "admin"  //default case
                 ,'cancelProject'               => "editProject"
                 ,'create_projectProject'       => "createProject"
                 ,'create_subprojectProject'    => "createProject"
                 ,'diffProject'                 => "editProject"
                 ,'ftpProject'                  => 'ftpProject'
                 ,'new_documentProject'         => "editProject"
                 ,'new_folderProject'           => "editProject"
                 ,'partialProject'              => "editProject"
                 ,'remove_project_draftProject' => "editProject"
                 ,'remove_projectProject'       => "deleteProject"
                 ,'rename_projectProject'       => "responsableProject"
                 ,'save_project_draftProject'   => "editProject"
                 ,'saveProject'                 => "editProject"
                 ,'viewProject'                 => "viewProject"
                 ,'_none'                       => "basicCommand"
                ];
        $this->authCfg = $aCfg;
    }
}
