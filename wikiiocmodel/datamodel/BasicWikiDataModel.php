<?php

if (!defined("DOKU_INC")) die();
require_once DOKU_INC . "inc/media.php";
require_once DOKU_INC . "inc/pageutils.php";
require_once DOKU_INC . "inc/common.php";

class BasicWikiDataModel extends AbstractWikiDataModel{
    protected $id;

    public function init($id) {
        $this->id = $id;
    }

    public function getData() {
        throw new UnavailableMethodExecutionException();
    }

    public function setData($toSet) {
        throw new UnavailableMethodExecutionException();
    }

}
