<?php
/**
 * Class abstract_writer_command_class: Classe abstracta de la qual hereten els altres commands.
 * @author Josep Cañellas <jcanell4@ioc.cat>
 */
if(!defined('DOKU_INC')) die();
require_once(DOKU_INC."inc/plugin.php");
require_once(DOKU_INC."inc/events.php");

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
