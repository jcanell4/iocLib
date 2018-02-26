<?php
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_INC."inc/plugin.php");
require_once(DOKU_INC."inc/events.php");
include_once(DOKU_INC."inc/inc_ioc/Logger.php"); //USO: Logger::debug($Texto, $NúmError, __LINE__, __FILE__, $level=-1, $append);
require_once(DOKU_PLUGIN."ajaxcommand/defkeys/ProjectKeys.php");

/**
 * Class abstract_command_class
 * Classe abstracta de la que hereten els altres commands.
 *
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
abstract class abstract_writer_command_class extends abstract_command_class {
    public function __construct( $modelAdapter=NULL, $authorization=NULL ) {
        parent::__construct($modelAdapter, $authorization);
    }

    public function isEmptyText() {
        if(isset($this->params[PageKeys::KEY_WIKITEXT])){
            $text = trim($this->params[PageKeys::KEY_PRE].
                     $this->params[PageKeys::KEY_WIKITEXT].
                     $this->params[PageKeys::KEY_SUF]
                    );
            return ($text == ".");
        }else{
            return FALSE;
        }
    }
}
