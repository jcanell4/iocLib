<?php
/**
 * Description of WikiRenderizableDataModel
 * @author professor
 */
if (!defined("DOKU_INC")) die();

abstract class WikiRenderizableDataModel extends AbstractWikiDataModel{

    protected $format;

    public function getFormat() {
        return $this->format;
    }

    //JOSEP: Això és necessari per poder passar a l'AbstractWikiDataModel el paràmetre del cosntructor!
    public function __construct($persistenceEngine){
        parent::__construct($persistenceEngine);
    }

    //JOSEP: Aquest canvi no és correcte.
    //          Quina diferència hi ha ara entre WikiRenderizableDataModel i AbstractWikiDataModel?
    //          Prerquè getData no fa res?
    //          La idea era que els datamodel renderitzables són aquells que mostres dos tipos de dades, les crues (getRawData) i les rendaritzades (getViewData).
    //          Per defecte getData són les dades rendaritzades
//    public function getData() {}

    //JOSEP: HO CANVIO A COM ESTAVA ABANS
    public function getData() {
        return $this->getViewData();
    }
    public abstract function getViewData();
    public abstract function getRawData();

}
