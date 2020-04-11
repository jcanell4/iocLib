<?php
/**
 * Description of AbstractWikiModel
 * @culpable Rafael
 */
abstract class AbstractWikiModel {

    protected $persistenceEngine;

    public function __construct($persistenceEngine) {
        $this->persistenceEngine = $persistenceEngine;
    }

    public abstract function getData();

    public abstract function setData($toSet);

}
