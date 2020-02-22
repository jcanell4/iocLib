<?php
/**
 * WikiIocPluginAction: classe base de les classes action de plugins de projectes
 * @culpable Rafael Claver
 */
if (!defined("DOKU_INC")) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . "lib/plugins/");
if (!defined('WIKI_IOC_MODEL')) define('WIKI_IOC_MODEL', DOKU_PLUGIN . "wikiiocmodel/");
require_once (WIKI_IOC_MODEL . 'persistence/BasicPersistenceEngine.php');

class WikiIocPluginAction extends DokuWiki_Action_Plugin {

    protected $persistenceEngine;
    protected $projectMetaDataQuery;
    protected $projectType;

    public function __construct() {
        $this->persistenceEngine = new \BasicPersistenceEngine();
        $this->projectMetaDataQuery = $this->persistenceEngine->createProjectMetaDataQuery();
    }

    function register(Doku_Event_Handler $controller) {
        //NOTA: Los nombres de tipo de proyecto no pueden contener el caracter '_'
        $elem = explode("_", get_class($this));
        $plugin = $elem[count($elem)-1];

        $listProjects = $this->projectMetaDataQuery->getPluginProjectTypes($plugin);

        if (!empty($listProjects)) {
            foreach ($listProjects as $project) {
                $dir = realpath(DOKU_PLUGIN."$plugin/projects/$project")."/";
                $action = "{$dir}action.php";
                if (is_file($action)) {
                    require_once ($action);
                    $classe = "action_plugin_{$plugin}_projects_$project";
                    $accio = new $classe($project, $dir);
                    $accio->register($controller);
                }
            }
        }
    }
    
    function getProjectType(){
        return $this->projectType;
    }

}
