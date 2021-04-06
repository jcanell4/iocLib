<?php
/**
 * Description of UserListAction
 * @author josep
 */
if (!defined("DOKU_INC")) die();

class WiocclAction  extends AbstractWikiAction {


    public function responseProcess() {
        $paramsArr = $this->params;
        $ret = null;

        $resp = [];

        $this->params['generateStructure'] = true;

        switch ($paramsArr[AjaxKeys::FORMAT]) {

            case 'html':
                // El extra s'omple al translator i contindrá la estructura
                // ALERTA! també cal enviar el length de la estructura per afegir els nous nodes a partir d'aquesta

                $structure = [];

                // ALERTA! en cap cas es pot tractar d'un paràgraph


//                WiocclParser::setInner(true);

//                DW2HtmlParser::setInner(true);
                $resp['content'] = DW2HtmlTranslator::translate($this->params['content'], $this->params, $structure, TRUE);
//                DW2HtmlParser::setInner(false);

//                WiocclParser::setInner(false);


                // ALERTA! la estrutura generada afegeix un node root que sobra, ja que el wioccl enviat sempre és
                // correspón a un node i els seus descendents, per tant cal eliminar-lo


                // TODO! el nextRef s'ha de passar al translator
                // Com que s'ignora el index 0 i el primer correspon a l'arrel i no al nextRef, la posición del primer element fill serà l'acumulat per nextRef-2

                // ALERTA! Cal reajustar els nodes, l'arrel ha de ser el $this->>params['rootRef']
                // Alerta la resta de nodes han de començar a nextRef, per determinar si fem el c

                $resp['extra'] = $structure;

                break;

            case 'dw':
                // TODO: per ara no cal
                break;

        }


        return $resp;
    }

}
